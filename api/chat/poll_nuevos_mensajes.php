<?php
/**
 * API: Long Polling para Nuevos Mensajes
 * Espera hasta que haya nuevos mensajes o se agote el timeout
 */

require_once('../../core/db.php');
require_once('../../core/session.php');

header('Content-Type: application/json');

// Verificar autenticación
requireLogin();
$usuario_id = getUserId();
session_write_close(); // Liberar el bloqueo de sesión para que otras peticiones no esperen

// Validar parámetros
if (!isset($_GET['conversacion_id']) || !isset($_GET['ultimo_mensaje_id'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Faltan parámetros: conversacion_id, ultimo_mensaje_id'
    ]);
    exit;
}

$conversacion_id = (int)$_GET['conversacion_id'];
$ultimo_mensaje_id = (int)$_GET['ultimo_mensaje_id'];
$timeout = 25; // Segundos máximo de espera
$inicio = time();

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
    
    // Loop de polling
    while ((time() - $inicio) < $timeout) {
        // Buscar mensajes nuevos
        $sql = "SELECT id, es_admin, mensaje, leido, creado_en
                FROM chat_mensajes
                WHERE conversacion_id = ? AND id > ?
                ORDER BY creado_en ASC";
        
        $stmt = $conexion_store->prepare($sql);
        $stmt->bind_param("ii", $conversacion_id, $ultimo_mensaje_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            // Hay mensajes nuevos
            $mensajes = [];
            $mensajes_admin_ids = [];
            
            while ($row = $result->fetch_assoc()) {
                $mensajes[] = [
                    'id' => (int)$row['id'],
                    'es_admin' => (bool)$row['es_admin'],
                    'mensaje' => $row['mensaje'],
                    'leido' => (bool)$row['leido'],
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
                'nuevos_mensajes' => true,
                'mensajes' => $mensajes
            ]);
            exit;
        }
        
        // Esperar 1 segundo antes de volver a consultar
        sleep(1);
    }
    
    // Timeout alcanzado sin mensajes nuevos
    echo json_encode([
        'success' => true,
        'nuevos_mensajes' => false,
        'mensajes' => []
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error en polling'
    ]);
}
?>
