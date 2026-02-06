<?php
/**
 * API Admin: Obtener Todas las Conversaciones
 * Lista todas las conversaciones del sistema para administración
 */

require_once('../../../core/db.php');
require_once('../../../admin/includes/session.php');

header('Content-Type: application/json');

// Verificar autenticación de admin
if (!isAdminLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

// Parámetros opcionales
$estado = isset($_GET['estado']) ? $_GET['estado'] : null;
$categoria_id = isset($_GET['categoria_id']) ? (int)$_GET['categoria_id'] : null;
$limite = isset($_GET['limite']) ? (int)$_GET['limite'] : 50;
$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
$busqueda = isset($_GET['busqueda']) ? trim($_GET['busqueda']) : null;

// Validar estado
$estados_validos = ['abierta', 'cerrada', 'resuelta'];
if ($estado && !in_array($estado, $estados_validos)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Estado inválido'
    ]);
    exit;
}

try {
    // Construir query
    $sql = "SELECT 
                c.id,
                c.usuario_id,
                c.asunto,
                c.estado,
                c.creada_en,
                c.ultimo_mensaje_en,
                c.pedido_id,
                u.nombre as usuario_nombre,
                u.email as usuario_email,
                cat.nombre as categoria_nombre,
                cat.icono as categoria_icono,
                (SELECT COUNT(*) FROM chat_mensajes 
                 WHERE conversacion_id = c.id AND es_admin = 0 AND leido = 0) as mensajes_sin_leer,
                (SELECT mensaje FROM chat_mensajes 
                 WHERE conversacion_id = c.id 
                 ORDER BY creado_en DESC LIMIT 1) as ultimo_mensaje,
                (SELECT COUNT(*) FROM chat_mensajes 
                 WHERE conversacion_id = c.id) as total_mensajes
            FROM chat_conversaciones c
            INNER JOIN chat_categorias cat ON c.categoria_id = cat.id
            LEFT JOIN usuarios u ON c.usuario_id = u.id
            WHERE 1=1";
    
    $params = [];
    $types = "";
    
    if ($estado) {
        $sql .= " AND c.estado = ?";
        $params[] = $estado;
        $types .= "s";
    }
    
    if ($categoria_id) {
        $sql .= " AND c.categoria_id = ?";
        $params[] = $categoria_id;
        $types .= "i";
    }
    
    if ($busqueda) {
        $sql .= " AND (c.asunto LIKE ? OR u.nombre LIKE ? OR u.email LIKE ?)";
        $busqueda_param = "%$busqueda%";
        $params[] = $busqueda_param;
        $params[] = $busqueda_param;
        $params[] = $busqueda_param;
        $types .= "sss";
    }
    
    $sql .= " ORDER BY 
                CASE WHEN c.estado = 'abierta' THEN 0 ELSE 1 END,
                c.ultimo_mensaje_en DESC 
              LIMIT ? OFFSET ?";
    $params[] = $limite;
    $params[] = $offset;
    $types .= "ii";
    
    $stmt = $conexion_store->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    
    $conversaciones = [];
    while ($row = $result->fetch_assoc()) {
        $conversaciones[] = [
            'id' => (int)$row['id'],
            'usuario_id' => (int)$row['usuario_id'],
            'usuario_nombre' => $row['usuario_nombre'],
            'usuario_email' => $row['usuario_email'],
            'asunto' => $row['asunto'],
            'estado' => $row['estado'],
            'creada_en' => $row['creada_en'],
            'ultimo_mensaje_en' => $row['ultimo_mensaje_en'],
            'pedido_id' => $row['pedido_id'] ? (int)$row['pedido_id'] : null,
            'categoria' => [
                'nombre' => $row['categoria_nombre'],
                'icono' => $row['categoria_icono']
            ],
            'mensajes_sin_leer' => (int)$row['mensajes_sin_leer'],
            'total_mensajes' => (int)$row['total_mensajes'],
            'ultimo_mensaje' => $row['ultimo_mensaje']
        ];
    }
    
    // Obtener total de conversaciones (para paginación)
    $sql_count = "SELECT COUNT(*) as total FROM chat_conversaciones c 
                  LEFT JOIN usuarios u ON c.usuario_id = u.id WHERE 1=1";
    $count_params = [];
    $count_types = "";
    
    if ($estado) {
        $sql_count .= " AND c.estado = ?";
        $count_params[] = $estado;
        $count_types .= "s";
    }
    
    if ($categoria_id) {
        $sql_count .= " AND c.categoria_id = ?";
        $count_params[] = $categoria_id;
        $count_types .= "i";
    }
    
    if ($busqueda) {
        $sql_count .= " AND (c.asunto LIKE ? OR u.nombre LIKE ? OR u.email LIKE ?)";
        $count_params[] = $busqueda_param;
        $count_params[] = $busqueda_param;
        $count_params[] = $busqueda_param;
        $count_types .= "sss";
    }
    
    $stmt_count = $conexion_store->prepare($sql_count);
    if (!empty($count_params)) {
        $stmt_count->bind_param($count_types, ...$count_params);
    }
    $stmt_count->execute();
    $total = $stmt_count->get_result()->fetch_assoc()['total'];
    
    // Obtener estadísticas rápidas
    $stats_sql = "SELECT 
                    SUM(CASE WHEN estado = 'abierta' THEN 1 ELSE 0 END) as abiertas,
                    SUM(CASE WHEN estado = 'cerrada' THEN 1 ELSE 0 END) as cerradas,
                    SUM(CASE WHEN estado = 'resuelta' THEN 1 ELSE 0 END) as resueltas
                  FROM chat_conversaciones";
    $stats_result = $conexion_store->query($stats_sql);
    $stats = $stats_result->fetch_assoc();
    
    echo json_encode([
        'success' => true,
        'conversaciones' => $conversaciones,
        'total' => (int)$total,
        'limite' => $limite,
        'offset' => $offset,
        'estadisticas' => [
            'abiertas' => (int)$stats['abiertas'],
            'cerradas' => (int)$stats['cerradas'],
            'resueltas' => (int)$stats['resueltas']
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error al obtener conversaciones'
    ]);
}
?>
