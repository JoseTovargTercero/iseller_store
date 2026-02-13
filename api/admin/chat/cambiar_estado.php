<?php
/**
 * API Admin: Cambiar Estado de Conversación
 * Permite cambiar el estado de una conversación (abierta/cerrada/resuelta)
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

// Obtener datos POST
$input = json_decode(file_get_contents('php://input'), true);

// Validar campos requeridos
if (!isset($input['conversacion_id']) || !isset($input['estado'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Faltan campos requeridos: conversacion_id, estado'
    ]);
    exit;
}

$conversacion_id = (int)$input['conversacion_id'];
$nuevo_estado = $input['estado'];

// Validar estado
$estados_validos = ['abierta', 'cerrada', 'resuelta'];
if (!in_array($nuevo_estado, $estados_validos)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Estado inválido. Valores permitidos: abierta, cerrada, resuelta'
    ]);
    exit;
}

try {
    // Verificar que la conversación existe
    $sql_check = "SELECT id, estado FROM chat_conversaciones WHERE id = ?";
    $stmt_check = $conexion_store->prepare($sql_check);
    $stmt_check->bind_param("i", $conversacion_id);
    $stmt_check->execute();
    $result = $stmt_check->get_result();
    
    if ($result->num_rows === 0) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Conversación no encontrada'
        ]);
        exit;
    }
    
    $conversacion = $result->fetch_assoc();
    $estado_anterior = $conversacion['estado'];
    
    // Actualizar estado
    $sql_update = "UPDATE chat_conversaciones 
                   SET estado = ?
                   WHERE id = ?";
    $stmt_update = $conexion_store->prepare($sql_update);
    $stmt_update->bind_param("si", $nuevo_estado, $conversacion_id);
    $stmt_update->execute();
    
    echo json_encode([
        'success' => true,
        'estado_anterior' => $estado_anterior,
        'estado_nuevo' => $nuevo_estado,
        'message' => 'Estado actualizado correctamente'
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error al actualizar estado:' . $e->getMessage()
    ]);
}
?>
