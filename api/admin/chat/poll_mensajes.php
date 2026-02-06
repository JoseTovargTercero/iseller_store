<?php
/**
 * API Admin: Polling para Nuevos Mensajes
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
$timeout = 25; 
$inicio = time();

try {
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
            $mensajes_user_ids = [];
            
            while ($row = $result->fetch_assoc()) {
                $mensajes[] = [
                    'id' => (int)$row['id'],
                    'es_admin' => (bool)$row['es_admin'],
                    'mensaje' => $row['mensaje'],
                    'leido' => (bool)$row['leido'],
                    'creado_en' => $row['creado_en']
                ];
                
                // Si el mensaje es del usuario y no está leído, marcarlo para lectura
                if (!$row['es_admin'] && !$row['leido']) {
                    $mensajes_user_ids[] = (int)$row['id'];
                }
            }
            
            // Marcar mensajes del usuario como leídos (porque el admin los está viendo)
            if (!empty($mensajes_user_ids)) {
                $ids_str = implode(',', $mensajes_user_ids);
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
        
        // Esperar antes de volver a consultar para no saturar
        sleep(1);
    }
    
    // Timeout alcanzado
    echo json_encode([
        'success' => true,
        'nuevos_mensajes' => false,
        'mensajes' => []
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error en polling admin'
    ]);
}
