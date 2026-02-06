<?php
/**
 * API: Obtener Conversaciones del Usuario
 * Lista todas las conversaciones del usuario actual
 */

require_once('../../core/db.php');
require_once('../../core/session.php');

header('Content-Type: application/json');

// Verificar autenticación
requireLogin();
$usuario_id = getUserId();

// Parámetros opcionales
$estado = isset($_GET['estado']) ? $_GET['estado'] : null;
$limite = isset($_GET['limite']) ? (int)$_GET['limite'] : 50;
$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;

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
                c.asunto,
                c.estado,
                c.creada_en,
                c.ultimo_mensaje_en,
                c.pedido_id,
                cat.nombre as categoria_nombre,
                cat.icono as categoria_icono,
                (SELECT COUNT(*) FROM chat_mensajes 
                 WHERE conversacion_id = c.id AND es_admin = 1 AND leido = 0) as mensajes_sin_leer,
                (SELECT mensaje FROM chat_mensajes 
                 WHERE conversacion_id = c.id 
                 ORDER BY creado_en DESC LIMIT 1) as ultimo_mensaje
            FROM chat_conversaciones c
            INNER JOIN chat_categorias cat ON c.categoria_id = cat.id
            WHERE c.usuario_id = ?";
    
    $params = [$usuario_id];
    $types = "i";
    
    if ($estado) {
        $sql .= " AND c.estado = ?";
        $params[] = $estado;
        $types .= "s";
    }
    
    $sql .= " ORDER BY c.ultimo_mensaje_en DESC LIMIT ? OFFSET ?";
    $params[] = $limite;
    $params[] = $offset;
    $types .= "ii";
    
    $stmt = $conexion_store->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $conversaciones = [];
    while ($row = $result->fetch_assoc()) {
        $conversaciones[] = [
            'id' => (int)$row['id'],
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
            'ultimo_mensaje' => $row['ultimo_mensaje']
        ];
    }
    
    // Obtener total de conversaciones (para paginación)
    $sql_count = "SELECT COUNT(*) as total FROM chat_conversaciones WHERE usuario_id = ?";
    if ($estado) {
        $sql_count .= " AND estado = ?";
    }
    $stmt_count = $conexion_store->prepare($sql_count);
    if ($estado) {
        $stmt_count->bind_param("is", $usuario_id, $estado);
    } else {
        $stmt_count->bind_param("i", $usuario_id);
    }
    $stmt_count->execute();
    $total = $stmt_count->get_result()->fetch_assoc()['total'];
    
    echo json_encode([
        'success' => true,
        'conversaciones' => $conversaciones,
        'total' => (int)$total,
        'limite' => $limite,
        'offset' => $offset
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error al obtener conversaciones'
    ]);
}
?>
