<?php
header('Content-Type: application/json');
require_once('../core/db.php');
require_once('../core/session.php');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Debes iniciar sesión para guardar el carrito']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

$user_id = getUserId();
$name = $_POST['name'] ?? 'Carrito sin nombre';
$content = $_POST['content'] ?? null;

if (!$content) {
    echo json_encode(['success' => false, 'message' => 'El carrito está vacío']);
    exit;
}

$stmt = $conexion_store->prepare("INSERT INTO saved_carts (user_id, name, content) VALUES (?, ?, ?)");
$stmt->bind_param("iss", $user_id, $name, $content);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Carrito guardado correctamente']);
} else {
    echo json_encode(['success' => false, 'message' => 'Error al guardar el carrito']);
}

$stmt->close();
?>
