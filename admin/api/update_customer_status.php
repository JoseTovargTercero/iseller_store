<?php
// admin/api/update_customer_status.php
header('Content-Type: application/json');
require_once dirname(__DIR__) . '/config/db.php';
require_once dirname(__DIR__) . '/includes/session.php';

requireAdminLogin();

$data = json_decode(file_get_contents('php://input'), true);
$userId = $data['id'] ?? null;
$newStatus = $data['estado'] ?? null; // 1: activo, 0: baneado

if ($userId === null || $newStatus === null) {
    echo json_encode(['success' => false, 'message' => 'Parámetros faltantes']);
    exit;
}

$stmt = $conexion_store->prepare("UPDATE usuarios SET estado = ? WHERE id = ?");
$stmt->bind_param("ii", $newStatus, $userId);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Error al actualizar el estado']);
}
?>
