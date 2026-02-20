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
    // 1. Obtener usuario_id y compra_id de la relación
    $stmt = $conexion_store->prepare("SELECT usuario_id, compra_id FROM compras_por_usuarios WHERE id = ?");
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
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    $conexion_store->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
