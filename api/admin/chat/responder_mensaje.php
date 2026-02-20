<?php
/**
 * API Admin: Responder Mensaje
 * Permite al admin enviar respuestas en una conversación
 */

require_once('../../../core/db.php');
require_once('../../../admin/includes/session.php');
require_once('../../chat/chat_helpers.php');

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
if (!isset($input['conversacion_id']) || !isset($input['mensaje'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Faltan campos requeridos: conversacion_id, mensaje'
    ]);
    exit;
}

$conversacion_id = (int)$input['conversacion_id'];
$mensaje = trim($input['mensaje']);

// Validar longitud del mensaje
if (strlen($mensaje) < 1 || strlen($mensaje) > 5000) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'El mensaje debe tener entre 1 y 5000 caracteres'
    ]);
    exit;
}

try {
    // Verificar que la conversación existe
    $sql_check = "SELECT id, usuario_id, estado FROM chat_conversaciones WHERE id = ?";
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
    
    // Iniciar transacción
    $conexion_store->begin_transaction();
    
    // Insertar mensaje del admin
    $sql_msg = "INSERT INTO chat_mensajes 
                (conversacion_id, es_admin, mensaje, leido) 
                VALUES (?, 1, ?, 0)";
    $stmt_msg = $conexion_store->prepare($sql_msg);
    $stmt_msg->bind_param("is", $conversacion_id, $mensaje);
    $stmt_msg->execute();
    $mensaje_id = $conexion_store->insert_id;
    
    // Actualizar timestamp de último mensaje en la conversación
    $sql_update = "UPDATE chat_conversaciones 
                   SET ultimo_mensaje_en = NOW()
                   WHERE id = ?";
    $stmt_update = $conexion_store->prepare($sql_update);
    $stmt_update->bind_param("i", $conversacion_id);
    $stmt_update->execute();
    
    // Commit transacción
    $conexion_store->commit();
    
    // Enviar notificación al usuario (vía Correo)
    enviarNotificacionRespuestaAdmin($conversacion_id, $mensaje);
    
    echo json_encode([
        'success' => true,
        'mensaje_id' => $mensaje_id,
        'creado_en' => date('Y-m-d H:i:s'),
        'message' => 'Mensaje enviado correctamente'
    ]);
    
} catch (Exception $e) {
    $conexion_store->rollback();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error al enviar mensaje'
    ]);
}
?>
