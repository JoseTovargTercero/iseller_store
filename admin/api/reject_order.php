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

$stmt = $conexion_store->prepare("UPDATE compras_por_usuarios SET estado = 'rechazada', motivo_rechazo = ? WHERE id = ?");
$stmt->bind_param("si", $motivo, $id);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Error al rechazar: ' . $stmt->error]);
}
?>
