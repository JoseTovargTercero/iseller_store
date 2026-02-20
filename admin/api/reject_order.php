<?php
// admin/api/reject_order.php
header('Content-Type: application/json');
require_once dirname(__DIR__) . '/config/db.php';
require_once dirname(__DIR__) . '/includes/session.php';

requireAdminLogin();

$input = json_decode(file_get_contents('php://input'), true);
$id = $input['id'] ?? null;
$motivo = trim($input['motivo'] ?? '');

if (!$id || empty($motivo)) {
    echo json_encode(['success' => false, 'message' => 'Se requiere ID y motivo de rechazo']);
    exit;
}

$conexion_store->begin_transaction();

try {
    // 1. Obtener usuario_id, compra_id y datos del usuario
    $stmt = $conexion_store->prepare("
        SELECT c.usuario_id, c.compra_id, c.orden_id, u.nombre, u.email 
        FROM compras_por_usuarios c
        JOIN usuarios u ON c.usuario_id = u.id
        WHERE c.id = ?
    ");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $res = $stmt->get_result();
    $data = $res->fetch_assoc();
    $stmt->close();

    if (!$data) {
        throw new Exception("No se encontró la compra especificada");
    }

    $usuario_id = $data['usuario_id'];
    $compra_id_internal = $data['compra_id'];
    $orden_id = $data['orden_id'];
    $user_name = $data['nombre'];
    $user_email = $data['email'];

    // 2. Buscar puntos aplicados en historial_puntos
    $stmt = $conexion_store->prepare("SELECT puntos_aplicados FROM historial_puntos WHERE compra_id = ?");
    $stmt->bind_param("i", $compra_id_internal);
    $stmt->execute();
    $resPuntos = $stmt->get_result();
    
    if ($rowPuntos = $resPuntos->fetch_assoc()) {
        $puntos_a_restar = floatval($rowPuntos['puntos_aplicados']);
        $stmt->close();

        // 3. Restar puntos al usuario
        $stmtUpd = $conexion_store->prepare("UPDATE usuarios SET puntos = puntos - ? WHERE id = ?");
        $stmtUpd->bind_param("di", $puntos_a_restar, $usuario_id);
        if (!$stmtUpd->execute()) {
            throw new Exception("Error al actualizar puntos del usuario: " . $stmtUpd->error);
        }
        $stmtUpd->close();

        // 4. Borrar registro en historial_puntos
        $stmtDel = $conexion_store->prepare("DELETE FROM historial_puntos WHERE compra_id = ?");
        $stmtDel->bind_param("i", $compra_id_internal);
        if (!$stmtDel->execute()) {
            throw new Exception("Error al borrar historial de puntos: " . $stmtDel->error);
        }
        $stmtDel->close();
    } else {
        $stmt->close();
        // Si no hay historial de puntos, simplemente procedemos (podría ser una compra sin puntos)
    }

    // 5. Actualizar estado de la compra
    $stmt = $conexion_store->prepare("UPDATE compras_por_usuarios SET estado = 'rechazada', motivo_rechazo = ? WHERE id = ?");
    $stmt->bind_param("si", $motivo, $id);

    if (!$stmt->execute()) {
        throw new Exception("Error al rechazar la compra: " . $stmt->error);
    }
    $stmt->close();

    $conexion_store->commit();

    // Enviar notificación por correo
    enviarCorreoRechazo($user_email, $user_name, $orden_id, $motivo);

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    $conexion_store->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

/**
 * Envía un correo profesional al usuario informando el rechazo de su pedido
 */
function enviarCorreoRechazo($to, $nombre, $orden_id, $motivo) {
    if (!$to) return;

    $subject = "Actualización de tu pedido #$orden_id - iSeller Store";
    $from = 'pedidos@iseller-tiendas.com';
    
    $message = "
    <html>
    <head>
        <style>
            body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; color: #333; line-height: 1.6; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #eee; border-radius: 10px; }
            .header { background-color: #dc3545; color: white; padding: 20px; text-align: center; border-radius: 10px 10px 0 0; }
            .content { padding: 30px; background-color: #fff; }
            .status-box { background-color: #fff5f5; border-left: 5px solid #dc3545; padding: 15px; margin: 20px 0; }
            .reason-title { font-weight: bold; color: #dc3545; margin-bottom: 5px; }
            .footer { text-align: center; padding: 20px; font-size: 0.9em; color: #777; }
            .btn { display: inline-block; padding: 12px 25px; background-color: #6fb07f; color: white; text-decoration: none; border-radius: 5px; font-weight: bold; margin-top: 20px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1 style='margin:0;'>iSeller Store</h1>
            </div>
            <div class='content'>
                <p>Hola, <strong>$nombre</strong>,</p>
                <p>Lamentamos informarte que tu pedido <strong>#$orden_id</strong> ha sido rechazado.</p>
                
                <div class='status-box'>
                    <div class='reason-title'>🚫 Motivo del rechazo:</div>
                    <p style='margin:0;'>$motivo</p>
                </div>
                
                <p>Si consideras que esto es un error o deseas realizar una nueva compra, puedes visitar nuestra tienda o contactarnos directamente.</p>
                
                <div style='text-align: center;'>
                    <a href='https://iseller-tiendas.com/perfil.php' class='btn'>Ir a mi perfil</a>
                </div>
                
                <p>Gracias por tu comprensión.<br>
                <strong>Equipo iSeller Store</strong></p>
            </div>
            <div class='footer'>
                <p>Atentamente,<br>
                <strong>iSeller Store</strong><br>
                <a href='https://iseller-tiendas.com' style='color: #dc3545;'>https://iseller-tiendas.com</a></p>
            </div>
        </div>
    </body>
    </html>
    ";

    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: iSeller Store <$from>" . "\r\n";
    $headers .= "Reply-To: $from" . "\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion();

    @mail($to, $subject, $message, $headers);
}
?>
