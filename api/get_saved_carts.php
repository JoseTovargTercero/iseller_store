<?php
header('Content-Type: application/json');
require_once('../core/db.php');
require_once('../core/session.php');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Debes iniciar sesión']);
    exit;
}

$user_id = getUserId();

$sql = "SELECT id, name, created_at FROM saved_carts WHERE user_id = ? ORDER BY created_at DESC";
$stmt = $conexion_store->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$carts = [];
while ($row = $result->fetch_assoc()) {
    $carts[] = $row;
}

echo json_encode(['success' => true, 'carts' => $carts]);

$stmt->close();
?>
