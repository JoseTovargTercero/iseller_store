<?php
/**
 * API Admin: Obtener Mensajes de una Conversación
 * Versión para administración que soporta sesión de admin
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

// Validar parámetros
if (!isset($_GET['conversacion_id'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Falta parámetro: conversacion_id'
    ]);
    exit;
}

$conversacion_id = (int)$_GET['conversacion_id'];
$desde_id = isset($_GET['desde_id']) ? (int)$_GET['desde_id'] : 0;

try {
    // Obtener mensajes (el admin puede ver cualquier conversación)
    $sql = "SELECT id, es_admin, admin_id, mensaje, leido, leido_en, creado_en
            FROM chat_mensajes
            WHERE conversacion_id = ? AND id > ?
            ORDER BY creado_en ASC";
    
    $stmt = $conexion_store->prepare($sql);
    $stmt->bind_param("ii", $conversacion_id, $desde_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $mensajes = [];
    $mensajes_user_ids = [];
    
    while ($row = $result->fetch_assoc()) {
        $mensajes[] = [
            'id' => (int)$row['id'],
            'es_admin' => (bool)$row['es_admin'],
            'mensaje' => $row['mensaje'],
            'leido' => (bool)$row['leido'],
            'leido_en' => $row['leido_en'],
            'creado_en' => $row['creado_en']
        ];
        
        // Si el mensaje es del usuario y no está leído, marcar como leído (el admin lo está leyendo)
        if (!$row['es_admin'] && !$row['leido']) {
            $mensajes_user_ids[] = (int)$row['id'];
        }
    }
    
    // Marcar mensajes del usuario como leídos
    if (!empty($mensajes_user_ids)) {
        $ids_str = implode(',', $mensajes_user_ids);
        $sql_update = "UPDATE chat_mensajes 
                       SET leido = 1, leido_en = NOW() 
                       WHERE id IN ($ids_str)";
        $conexion_store->query($sql_update);
    }
    
    echo json_encode([
        'success' => true,
        'mensajes' => $mensajes,
        'total' => count($mensajes)
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error al obtener mensajes admin'
    ]);
}
