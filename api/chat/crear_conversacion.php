<?php
/**
 * API: Crear Nueva Conversación
 * Crea una conversación y envía el mensaje inicial
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
if (!isset($input['categoria_id']) || !isset($input['asunto']) || !isset($input['mensaje_inicial'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Faltan campos requeridos: categoria_id, asunto, mensaje_inicial'
    ]);
    exit;
}

$categoria_id = (int)$input['categoria_id'];
$asunto = trim($input['asunto']);
$mensaje_inicial = trim($input['mensaje_inicial']);
$pedido_id = isset($input['pedido_id']) ? (int)$input['pedido_id'] : null;

// Validar longitudes
if (strlen($asunto) < 5 || strlen($asunto) > 200) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'El asunto debe tener entre 5 y 200 caracteres'
    ]);
    exit;
}

if (strlen($mensaje_inicial) < 10 || strlen($mensaje_inicial) > 5000) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'El mensaje debe tener entre 10 y 5000 caracteres'
    ]);
    exit;
}

try {
    // Iniciar transacción
    $conexion_store->begin_transaction();
    
    // Validar que la categoría existe
    $sql_cat = "SELECT id FROM chat_categorias WHERE id = ? AND activo = 1";
    $stmt_cat = $conexion_store->prepare($sql_cat);
    $stmt_cat->bind_param("i", $categoria_id);
    $stmt_cat->execute();
    if ($stmt_cat->get_result()->num_rows === 0) {
        throw new Exception('Categoría inválida');
    }
    
    // Si se proporcionó pedido_id, validar que pertenece al usuario
    if ($pedido_id !== null) {
        $sql_pedido = "SELECT id FROM compras_por_usuarios WHERE id = ? AND usuario_id = ?";
        $stmt_pedido = $conexion_store->prepare($sql_pedido);
        $stmt_pedido->bind_param("ii", $pedido_id, $usuario_id);
        $stmt_pedido->execute();
        if ($stmt_pedido->get_result()->num_rows === 0) {
            throw new Exception('Pedido no encontrado o no pertenece al usuario');
        }
    }
    
    // Crear conversación
    $sql_conv = "INSERT INTO chat_conversaciones 
                 (usuario_id, pedido_id, categoria_id, asunto, estado, ultimo_mensaje_en) 
                 VALUES (?, ?, ?, ?, 'abierta', NOW())";
    $stmt_conv = $conexion_store->prepare($sql_conv);
    $stmt_conv->bind_param("iiis", $usuario_id, $pedido_id, $categoria_id, $asunto);
    $stmt_conv->execute();
    $conversacion_id = $conexion_store->insert_id;
    
    // Insertar mensaje inicial del usuario
    $sql_msg = "INSERT INTO chat_mensajes 
                (conversacion_id, es_admin, mensaje, leido) 
                VALUES (?, 0, ?, 0)";
    $stmt_msg = $conexion_store->prepare($sql_msg);
    $stmt_msg->bind_param("is", $conversacion_id, $mensaje_inicial);
    $stmt_msg->execute();
    $mensaje_id = $conexion_store->insert_id;
    
    // Obtener mensaje automático de bienvenida si existe
    $sql_auto = "SELECT mensaje FROM chat_mensajes_automaticos 
                 WHERE tipo = 'primera_respuesta' AND categoria_id = ? AND activo = 1 
                 LIMIT 1";
    $stmt_auto = $conexion_store->prepare($sql_auto);
    $stmt_auto->bind_param("i", $categoria_id);
    $stmt_auto->execute();
    $result_auto = $stmt_auto->get_result();
    
    $mensaje_automatico = null;
    if ($row_auto = $result_auto->fetch_assoc()) {
        // Insertar mensaje automático
        $mensaje_auto = $row_auto['mensaje'];
        $sql_msg_auto = "INSERT INTO chat_mensajes 
                         (conversacion_id, es_admin, mensaje, leido) 
                         VALUES (?, 1, ?, 0)";
        $stmt_msg_auto = $conexion_store->prepare($sql_msg_auto);
        $stmt_msg_auto->bind_param("is", $conversacion_id, $mensaje_auto);
        $stmt_msg_auto->execute();
        $mensaje_automatico = [
            'id' => $conexion_store->insert_id,
            'mensaje' => $mensaje_auto,
            'es_admin' => true,
            'creado_en' => date('Y-m-d H:i:s')
        ];
    }
    
    // Commit transacción
    $conexion_store->commit();
    
    // Enviar notificación a administración
    require_once('chat_helpers.php');
    enviarNotificacionNuevoMensaje($usuario_id, $conversacion_id, $mensaje_inicial);
    
    echo json_encode([
        'success' => true,
        'conversacion_id' => $conversacion_id,
        'mensaje_id' => $mensaje_id,
        'mensaje_automatico' => $mensaje_automatico
    ]);
    
} catch (Exception $e) {
    $conexion_store->rollback();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
