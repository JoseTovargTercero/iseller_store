<?php
/**
 * API: Enviar Mensaje
 * Envía un mensaje a una conversación existente
 */

require_once('../../core/db.php');
require_once('../../core/session.php');

header('Content-Type: application/json');

// Verificar autenticación
requireLogin();
$usuario_id = getUserId();

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
    // Verificar que la conversación existe y pertenece al usuario
    $sql_check = "SELECT id, estado FROM chat_conversaciones 
                  WHERE id = ? AND usuario_id = ?";
    $stmt_check = $conexion_store->prepare($sql_check);
    $stmt_check->bind_param("ii", $conversacion_id, $usuario_id);
    $stmt_check->execute();
    $result = $stmt_check->get_result();
    
    if ($result->num_rows === 0) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'message' => 'Conversación no encontrada o sin permisos'
        ]);
        exit;
    }
    
    $conv = $result->fetch_assoc();
    
    // Verificar que la conversación no esté cerrada
    if ($conv['estado'] === 'cerrada') {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'No se pueden enviar mensajes a conversaciones cerradas'
        ]);
        exit;
    }
    
    // Iniciar transacción
    $conexion_store->begin_transaction();
    
    // Insertar mensaje
    $sql_msg = "INSERT INTO chat_mensajes 
                (conversacion_id, es_admin, mensaje, leido) 
                VALUES (?, 0, ?, 0)";
    $stmt_msg = $conexion_store->prepare($sql_msg);
    $stmt_msg->bind_param("is", $conversacion_id, $mensaje);
    $stmt_msg->execute();
    $mensaje_id = $conexion_store->insert_id;
    
    // Actualizar timestamp de última actividad en la conversación
    $sql_update = "UPDATE chat_conversaciones 
                   SET ultimo_mensaje_en = NOW(), 
                       estado = 'abierta'
                   WHERE id = ?";
    $stmt_update = $conexion_store->prepare($sql_update);
    $stmt_update->bind_param("i", $conversacion_id);
    $stmt_update->execute();
    
    // Commit transacción
    $conexion_store->commit();
    
    // Enviar notificación a administración
    require_once('chat_helpers.php');
    enviarNotificacionNuevoMensaje($usuario_id, $conversacion_id, $mensaje);
    
    echo json_encode([
        'success' => true,
        'mensaje_id' => $mensaje_id,
        'creado_en' => date('Y-m-d H:i:s')
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
