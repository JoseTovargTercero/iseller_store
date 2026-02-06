<?php
/**
 * API: Obtener Mensajes de una Conversación
 * Retorna todos los mensajes de una conversación y marca como leídos
 */

require_once('../../core/db.php');
require_once('../../core/session.php');

header('Content-Type: application/json');

// Verificar autenticación
requireLogin();
$usuario_id = getUserId();

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
    // Verificar que la conversación existe y pertenece al usuario
    $sql_check = "SELECT id FROM chat_conversaciones 
                  WHERE id = ? AND usuario_id = ?";
    $stmt_check = $conexion_store->prepare($sql_check);
    $stmt_check->bind_param("ii", $conversacion_id, $usuario_id);
    $stmt_check->execute();
    
    if ($stmt_check->get_result()->num_rows === 0) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'message' => 'Conversación no encontrada o sin permisos'
        ]);
        exit;
    }
    
    // Obtener mensajes
    $sql = "SELECT id, es_admin, admin_id, mensaje, leido, leido_en, creado_en
            FROM chat_mensajes
            WHERE conversacion_id = ? AND id > ?
            ORDER BY creado_en ASC";
    
    $stmt = $conexion_store->prepare($sql);
    $stmt->bind_param("ii", $conversacion_id, $desde_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $mensajes = [];
    $mensajes_admin_ids = [];
    
    while ($row = $result->fetch_assoc()) {
        $mensajes[] = [
            'id' => (int)$row['id'],
            'es_admin' => (bool)$row['es_admin'],
            'mensaje' => $row['mensaje'],
            'leido' => (bool)$row['leido'],
            'leido_en' => $row['leido_en'],
            'creado_en' => $row['creado_en']
        ];
        
        // Guardar IDs de mensajes del admin para marcar como leídos
        if ($row['es_admin'] && !$row['leido']) {
            $mensajes_admin_ids[] = (int)$row['id'];
        }
    }
    
    // Marcar mensajes del admin como leídos
    if (!empty($mensajes_admin_ids)) {
        $ids_str = implode(',', $mensajes_admin_ids);
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
        'message' => 'Error al obtener mensajes'
    ]);
}
?>
