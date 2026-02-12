<?php
header('Content-Type: application/json');
require_once('../core/db.php');
require_once('../core/session.php');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Debes iniciar sesión']);
    exit;
}

$id = $_GET['id'] ?? null;
$user_id = getUserId();

if (!$id) {
    echo json_encode(['success' => false, 'message' => 'ID de carrito no proporcionado']);
    exit;
}

$stmt = $conexion_store->prepare("SELECT content FROM saved_carts WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    echo json_encode(['success' => true, 'content' => json_decode($row['content'], true)]);
} else {
    echo json_encode(['success' => false, 'message' => 'Carrito no encontrado']);
}

$stmt->close();
?>
