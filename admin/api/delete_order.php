<?php
// admin/api/delete_order.php
header('Content-Type: application/json');
require_once dirname(__DIR__) . '/config/db.php';
require_once dirname(__DIR__) . '/includes/session.php';

requireAdminLogin();

$input = json_decode(file_get_contents('php://input'), true);
$id = $input['id'] ?? null;

if (!$id) {
    echo json_encode(['success' => false, 'message' => 'ID requerido']);
    exit;
}

// Validar que esté rechazada antes de borrar (Regla: "Después de estar en estado rechazada: Mostrar botón Eliminar")
$check = $conexion_store->prepare("SELECT estado FROM compras_por_usuarios WHERE id = ?");
$check->bind_param("i", $id);
$check->execute();
$res = $check->get_result();
$row = $res->fetch_assoc();

if (!$row || $row['estado'] !== 'rechazada') {
    echo json_encode(['success' => false, 'message' => 'Solo se pueden eliminar órdenes rechazadas']);
    exit;
}

$stmt = $conexion_store->prepare("UPDATE compras_por_usuarios SET deleted_at = NOW() WHERE id = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Error al eliminar: ' . $stmt->error]);
}
?>
