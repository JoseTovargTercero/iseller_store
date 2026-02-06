<?php
/**
 * API: Obtener Configuración de la Tienda
 * Retorna información de la tienda para mostrar en el chat
 */

require_once('../../core/db.php');

header('Content-Type: application/json');

try {
    $sql = "SELECT nombre_comercial, logo_url, horario_atencion, 
                   tiempo_respuesta_estimado, telefono, email,
                   mensaje_bienvenida, chat_activo
            FROM tienda_configuracion 
            LIMIT 1";
    
    $stmt = $conexion_store->prepare($sql);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        echo json_encode([
            'success' => true,
            'config' => [
                'nombre_comercial' => $row['nombre_comercial'],
                'logo_url' => $row['logo_url'],
                'horario_atencion' => $row['horario_atencion'],
                'tiempo_respuesta_estimado' => $row['tiempo_respuesta_estimado'],
                'telefono' => $row['telefono'],
                'email' => $row['email'],
                'mensaje_bienvenida' => $row['mensaje_bienvenida'],
                'chat_activo' => (bool)$row['chat_activo']
            ]
        ]);
    } else {
        // Configuración por defecto si no existe en BD
        echo json_encode([
            'success' => true,
            'config' => [
                'nombre_comercial' => 'iSeller Store',
                'logo_url' => null,
                'horario_atencion' => 'Lun-Vie 9:00 AM - 6:00 PM',
                'tiempo_respuesta_estimado' => '~2 horas',
                'telefono' => null,
                'email' => null,
                'mensaje_bienvenida' => '¡Hola! 👋 Bienvenido a nuestro canal de soporte. ¿En qué podemos ayudarte hoy?',
                'chat_activo' => true
            ]
        ]);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error al obtener configuración'
    ]);
}
?>
