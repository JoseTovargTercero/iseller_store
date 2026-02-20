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
?>
