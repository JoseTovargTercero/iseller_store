<?php
header('Content-Type: application/json');

require_once dirname(__DIR__) . '/config/db.php';
require_once dirname(__DIR__) . '/includes/session.php';

requireAdminLogin();

$input = json_decode(file_get_contents('php://input'), true);

$id        = $input['id'] ?? null;     // compras_por_usuarios.id
$newStatus = $input['status'] ?? null;

if (!$id || !$newStatus) {
    echo json_encode(['success' => false, 'message' => 'Faltan datos']);
    exit;
}

$allowed = ['pendiente', 'en_revision', 'enviada', 'entregada'];
if (!in_array($newStatus, $allowed)) {
    echo json_encode(['success' => false, 'message' => 'Estado no válido']);
    exit;
}

/* =========================
   INICIAR TRANSACCIONES
========================= */

$conexion_store->begin_transaction();
$conexion->begin_transaction();

try {

    /* =========================
       ESTADO ACTUAL
    ========================= */

    $stmt = $conexion_store->prepare("
        SELECT c.estado, c.compra_id, c.usuario_id, c.valor_compra, u.nombre, u.email
        FROM compras_por_usuarios c
        JOIN usuarios u ON c.usuario_id = u.id
        WHERE c.id = ?
        FOR UPDATE
    ");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $res = $stmt->get_result();

    if (!$row = $res->fetch_assoc()) {
        throw new Exception('Orden no encontrada');
    }

    $current   = $row['estado'];
    $compra_id = $row['compra_id'];
    $user_id   = $row['usuario_id'];
    $valor     = $row['valor_compra'];
    $user_name = $row['nombre'];
    $user_email = $row['email'];

    /* =========================
       VALIDAR FLUJO
    ========================= */

    $flow = [
        'pendiente'   => ['en_revision'],
        'en_revision' => ['pendiente','enviada'],
        'enviada'     => ['en_revision','entregada'],
        'entregada'   => []
    ];

    if (!in_array($newStatus, $flow[$current])) {
        throw new Exception("Transición no permitida: $current → $newStatus");
    }



    /* =========================
       ARTÍCULOS DE LA ORDEN
    ========================= */

    $stmtArt = $conexion_store->prepare("
        SELECT product_id, quantity, id_sucursal
        FROM orden_articulos
        WHERE order_id = ?
    ");
    $stmtArt->bind_param("i", $compra_id);
    $stmtArt->execute();
    $resArt = $stmtArt->get_result();

    $articulos = [];
    while ($a = $resArt->fetch_assoc()) {
        $articulos[] = $a;
    }

    /* =========================
       CASO 1: PASA A ENVIADA → DESCONTAR STOCK
    ========================= */

    if ($current !== 'enviada' && $newStatus === 'enviada') {

        foreach ($articulos as $art) {

            $pid = $art['product_id'];
            $qty = (int)$art['quantity'];
            $suc = 9;

            // lock de stock
            $stmtChk = $conexion->prepare("
                SELECT stock
                FROM stock
                WHERE id_producto = ? AND id_sucursal = ?
                FOR UPDATE
            ");
            $stmtChk->bind_param("ii", $pid, $suc);
            $stmtChk->execute();
            $resChk = $stmtChk->get_result()->fetch_assoc();

            if (!$resChk) {
                throw new Exception("No existe stock para producto $pid");
            }

            if ((int)$resChk['stock'] < $qty) {
                throw new Exception("Stock insuficiente para producto $pid");
            }

            // descontar
            $stmtStock = $conexion->prepare("
                UPDATE stock
                SET stock = stock - ?
                WHERE id_producto = ? AND id_sucursal = ?
            ");
            $stmtStock->bind_param("iii", $qty, $pid, $suc);

            if (!$stmtStock->execute()) {
                throw new Exception('Error al descontar stock');
            }
        }

        if ((float)$valor >= 2) {

            // 1. Validar que sea la PRIMERA compra del usuario
            $stmtCompras = $conexion_store->prepare("
                SELECT COUNT(*) AS total
                FROM compras_por_usuarios
                WHERE usuario_id = ?
            ");
            $stmtCompras->bind_param("i", $user_id);
            $stmtCompras->execute();
            $totalCompras = $stmtCompras->get_result()->fetch_assoc()['total'];

            // Solo si es la primera compra
            if ($totalCompras == 1) {

                // 2. Consultar si existe referido pendiente
                $stmtRef = $conexion_store->prepare("
                    SELECT id, referrer_user_id
                    FROM referrals 
                    WHERE referred_user_id = ? 
                    AND status = 'pending'
                    LIMIT 1
                ");
                $stmtRef->bind_param("i", $user_id);
                $stmtRef->execute();
                $resRef = $stmtRef->get_result();

                if ($resRef->num_rows > 0) {

                    $ref = $resRef->fetch_assoc();
                    $referrer_user_id = $ref['referrer_user_id'];

                    // 3. Confirmar referido
                    $stmtRefUpdate = $conexion_store->prepare("
                        UPDATE referrals 
                        SET status = 'completed', purchase_id = ?,
                            completed_at = NOW()
                        WHERE id = ?
                    ");
                    $stmtRefUpdate->bind_param("ii", $compra_id, $ref['id']);
                    $stmtRefUpdate->execute();
                    $stmtRefUpdate->close();

                    /* =========================
                    DATOS DEL USUARIO
                    ========================= */

                    $stmtUser = $conexion_store->prepare("
                        SELECT nivel, puntos
                        FROM usuarios
                        WHERE id = ?
                    ");
                    $stmtUser->bind_param("i", $referrer_user_id);
                    $stmtUser->execute();
                    $resUser = $stmtUser->get_result();
                    $user = $resUser->fetch_assoc();
                    $user_nivel = $user['nivel'];
                    $user_puntos = $user['puntos'];


                    // Registrar recompensa del referrer en recompensas_usuario
                    $stmtRecompensa = $conexion_store->prepare("INSERT INTO recompensas_usuario (usuario_id, nivel_desbloqueo, tipo, monto, estado, fecha_creacion, compra_id)
                        VALUES (?, ?, 'referido', 0, 'disponible', NOW(), ?)");
                        if (!$stmtRecompensa) {
                            throw new Exception('Error al registrar recompensa del referrer' . $conexion_store->error);
                        }
                    $stmtRecompensa->bind_param("iii", $referrer_user_id, $user_nivel, $compra_id);
                    if (!$stmtRecompensa->execute()) {
                        throw new Exception('Error al registrar recompensa del referrer' . $stmtRecompensa->error);
                    }
                    $stmtRecompensa->close();


                    // Actualizar puntos del referrer
                    $stmtUpdatePoints = $conexion_store->prepare("UPDATE usuarios SET puntos = puntos + 3 WHERE id = ?");
                    $stmtUpdatePoints->bind_param("i", $referrer_user_id);
                    $stmtUpdatePoints->execute();
                    $stmtUpdatePoints->close();

                    $puntos_totales = $user_puntos + 3;
                    $nivel_calculado = intdiv($puntos_totales, 10);

                    if ($nivel_calculado > $user_nivel) {
                        $stmtUpdateLevel = $conexion_store->prepare("
                            UPDATE usuarios 
                            SET nivel = ?
                            WHERE id = ?
                        ");
                        $stmtUpdateLevel->bind_param("ii", $nivel_calculado, $referrer_user_id);
                        $stmtUpdateLevel->execute();
                        $stmtUpdateLevel->close();
                    }

                }
            }
        }

    }

    /* =========================
       CASO 2: RETROCEDE DE ENVIADA → EN_REVISION → DEVOLVER STOCK
    ========================= */

    if ($current === 'enviada' && $newStatus === 'en_revision') {

        foreach ($articulos as $art) {

            $pid = $art['product_id'];
            $qty = (int)$art['quantity'];
            $suc = 9;

            $stmtStock = $conexion->prepare("
                UPDATE stock
                SET stock = stock + ?
                WHERE id_producto = ? AND id_sucursal = ?
            ");
            $stmtStock->bind_param("iii", $qty, $pid, $suc);

            if (!$stmtStock->execute()) {
                throw new Exception('Error al devolver stock');
            }
        }

        // =========================
        // Reversión del referido y recompensas
        // =========================

        $stmtRef = $conexion_store->prepare("
            SELECT id, referrer_user_id
            FROM referrals
            WHERE referred_user_id = ? AND status = 'completed' AND purchase_id = ?
            LIMIT 1
        ");
        $stmtRef->bind_param("ii", $user_id, $compra_id);
        $stmtRef->execute();
        $resRef = $stmtRef->get_result();

        if ($resRef->num_rows > 0) {

            $ref = $resRef->fetch_assoc();
            $referrer_user_id = $ref['referrer_user_id'];

            // 1. Cambiar estado del referido a pending y limpiar fecha/compras
            $stmtRefUpdate = $conexion_store->prepare("
                UPDATE referrals
                SET status = 'pending',
                    completed_at = NULL,
                    purchase_id = NULL
                WHERE id = ?
            ");
            $stmtRefUpdate->bind_param("i", $ref['id']);
            $stmtRefUpdate->execute();
            $stmtRefUpdate->close();

            // 2. Eliminar recompensa asociada al referido
            $stmtDelRecompensa = $conexion_store->prepare("
                DELETE FROM recompensas_usuario
                WHERE usuario_id = ? AND tipo = 'referido' AND compra_id = ?
            ");
            $stmtDelRecompensa->bind_param("ii", $referrer_user_id, $compra_id);
            $stmtDelRecompensa->execute();
            $stmtDelRecompensa->close();

            // 3. Restar los puntos otorgados
            $stmtUpdatePoints = $conexion_store->prepare("
                UPDATE usuarios 
                SET puntos = puntos - 3
                WHERE id = ?
            ");
            $stmtUpdatePoints->bind_param("i", $referrer_user_id);
            $stmtUpdatePoints->execute();
            $stmtUpdatePoints->close();

            // 4. Actualizar nivel basado en los puntos restantes
            $stmtUser = $conexion_store->prepare("
                SELECT puntos, nivel
                FROM usuarios
                WHERE id = ?
            ");
            $stmtUser->bind_param("i", $referrer_user_id);
            $stmtUser->execute();
            $user = $stmtUser->get_result()->fetch_assoc();
            $stmtUser->close();

            $nivel_calculado = intdiv($user['puntos'], 10);

            if ($nivel_calculado != $user['nivel']) {
                $stmtUpdateLevel = $conexion_store->prepare("
                    UPDATE usuarios 
                    SET nivel = ?
                    WHERE id = ?
                ");
                $stmtUpdateLevel->bind_param("ii", $nivel_calculado, $referrer_user_id);
                $stmtUpdateLevel->execute();
                $stmtUpdateLevel->close();
            }
        }
    }

    /* =========================
       ACTUALIZAR ESTADO EN ECOMMERCE
    ========================= */

    $stmtUpd = $conexion_store->prepare("
        UPDATE compras_por_usuarios
        SET estado = ?
        WHERE id = ?
    ");
    $stmtUpd->bind_param("si", $newStatus, $id);

    if (!$stmtUpd->execute()) {
        throw new Exception('Error actualizando estado');
    }

    /* =========================
       COMMIT COORDINADO
    ========================= */

    $conexion->commit();        // inventario
    $conexion_store->commit();  // ecommerce

    // Enviar notificación por correo
    enviarCorreoEstado($user_email, $user_name, $id, $newStatus);

    echo json_encode(['success' => true]);

} catch (Exception $e) {

    $conexion->rollback();
    $conexion_store->rollback();

    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

/**
 * Envía un correo profesional al usuario informando el cambio de estado de su pedido
 */
function enviarCorreoEstado($to, $nombre, $orden_id, $nuevo_estado) {
    if (!$to) return;

    $subject = "Actualización de tu pedido #$orden_id - iSeller Store";
    $from = 'pedidos@iseller-tiendas.com';
    
    $estados = [
        'pendiente' => 'Pendiente',
        'en_revision' => 'En revisión',
        'enviada' => 'Enviada / Confirmada',
        'entregada' => 'Entregada'
    ];
    
    $estado_label = $estados[$nuevo_estado] ?? ucfirst($nuevo_estado);

    $message = "
    <html>
    <head>
        <style>
            body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; color: #333; line-height: 1.6; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #eee; border-radius: 10px; }
            .header { background-color: #6fb07f; color: white; padding: 20px; text-align: center; border-radius: 10px 10px 0 0; }
            .content { padding: 30px; background-color: #fff; }
            .status-box { background-color: #f8f9fa; border-left: 5px solid #6fb07f; padding: 15px; margin: 20px 0; font-weight: bold; font-size: 1.1em; }
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
                <p>Queremos informarte que el estado de tu pedido <strong>#$orden_id</strong> ha sido actualizado.</p>
                
                <div class='status-box'>
                    🔔 Estado actual: $estado_label
                </div>
                
                <p>Puedes consultar todos los detalles de tu compra, el progreso de la entrega y el historial de tu pedido desde nuestra aplicación o sitio web.</p>
                
                <div style='text-align: center;'>
                    <a href='https://iseller-tiendas.com/perfil.php' class='btn'>Ver mi pedido</a>
                </div>
                
                <p>Gracias por confiar en <strong>iSeller Store</strong>.<br>
                Seguimos trabajando para brindarte la mejor experiencia de compra.</p>
            </div>
            <div class='footer'>
                <p>Atentamente,<br>
                <strong>Equipo iSeller Store</strong><br>
                <a href='https://iseller-tiendas.com' style='color: #6fb07f;'>https://iseller-tiendas.com</a></p>
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
