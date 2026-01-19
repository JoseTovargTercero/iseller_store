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
        SELECT estado, compra_id
        FROM compras_por_usuarios
        WHERE id = ?
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

    echo json_encode(['success' => true]);

} catch (Exception $e) {

    $conexion->rollback();
    $conexion_store->rollback();

    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
