<?php
/**
 * Chat Helpers
 * Funciones compartidas para el sistema de chat
 */

function enviarNotificacionNuevoMensaje($usuario_id, $conversacion_id, $mensaje) {
    global $conexion_store;
    
    // Obtener datos del usuario
    $stmt = $conexion_store->prepare("SELECT nombre, email FROM usuarios WHERE id = ?");
    $stmt->bind_param("i", $usuario_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $usuario = $res->fetch_assoc();
    $stmt->close();
    
    $nombre_cliente = $usuario['nombre'] ?? 'Usuario Desconocido';
    $email_cliente = $usuario['email'] ?? 'N/A';

    $from = 'contacto@iseller-tiendas.com';
    $to = 'contacto@iseller-tiendas.com';
    $subject = "Nuevo Mensaje de Chat - iSeller Store";

    $message = "Has recibido un nuevo mensaje en el chat del administrador:\n\n";
    $message .= "Cliente: $nombre_cliente ($email_cliente)\n";
    $message .= "Conversación ID: #$conversacion_id\n";
    $message .= "Mensaje: \n\"$mensaje\"\n\n";
    $message .= "Favor ingresar al panel administrativo para responder.";

    $headers = "From: $from\r\n";
    $headers .= "Reply-To: $from\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion();

    @mail($to, $subject, $message, $headers);
}

function enviarNotificacionRespuestaAdmin($conversacion_id, $mensaje) {
    global $conexion_store;
    
    // Obtener datos de la conversación y del usuario
    $sql = "SELECT c.asunto, u.nombre, u.email 
            FROM chat_conversaciones c
            JOIN usuarios u ON c.usuario_id = u.id
            WHERE c.id = ?";
    $stmt = $conexion_store->prepare($sql);
    $stmt->bind_param("i", $conversacion_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $data = $res->fetch_assoc();
    $stmt->close();
    
    if (!$data || empty($data['email'])) return false;

    $nombre_cliente = $data['nombre'];
    $email_cliente = $data['email'];
    $asunto_original = $data['asunto'];

    $from = 'contacto@iseller-tiendas.com';
    $to = $email_cliente;
    $subject = "Nueva respuesta en tu chat: $asunto_original - iSeller Store";

    $storeUrl = 'https://iseller-tiendas.com'; // O la URL base configurada
    $nombreSeguro = htmlspecialchars($nombre_cliente, ENT_QUOTES, 'UTF-8');
    $mensajeSeguro = nl2br(htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8'));

    $htmlContent = <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 20px auto; border: 1px solid #e5e7eb; border-radius: 8px; overflow: hidden; }
        .header { background: #10b981; color: white; padding: 20px; text-align: center; }
        .content { padding: 30px; }
        .message-box { background: #f9fafb; border-left: 4px solid #10b981; padding: 15px; margin: 20px 0; font-style: italic; }
        .footer { background: #f3f4f6; color: #6b7280; padding: 20px; text-align: center; font-size: 12px; }
        .btn { display: inline-block; background: #10b981; color: white; padding: 12px 25px; text-decoration: none; border-radius: 5px; font-weight: bold; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>¡Tienes una nueva respuesta!</h2>
        </div>
        <div class="content">
            <p>Hola <strong>{$nombreSeguro}</strong>,</p>
            <p>Un administrador ha respondido a tu consulta sobre: <strong>{$asunto_original}</strong></p>
            
            <div class="message-box">
                {$mensajeSeguro}
            </div>
            
            <p>Puedes ver el historial completo y seguir la conversación en nuestra tienda:</p>
            <div style="text-align: center;">
                <a href="{$storeUrl}" class="btn">Ir a la Tienda</a>
            </div>
        </div>
        <div class="footer">
            <p>© 2026 iSeller Tiendas. Este es un correo automático, por favor no respondas directamente.</p>
        </div>
    </div>
</body>
</html>
HTML;

    $headers  = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "From: iSeller Tiendas <{$from}>\r\n";
    $headers .= "Reply-To: {$from}\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion();

    return @mail($to, $subject, $htmlContent, $headers);
}
?>
