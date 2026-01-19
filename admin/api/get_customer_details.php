<?php
// admin/api/get_customer_details.php
header('Content-Type: application/json');
require_once dirname(__DIR__) . '/config/db.php';
require_once dirname(__DIR__) . '/includes/session.php';

requireAdminLogin();

$userId = $_GET['id'] ?? null;

if (!$userId) {
    echo json_encode(['success' => false, 'message' => 'ID de usuario faltante']);
    exit;
}

// Get user info
$stmt = $conexion_store->prepare("SELECT id, nombre, email, telefono, nivel, estado, creado_en, puntos FROM usuarios WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    echo json_encode(['success' => false, 'message' => 'Usuario no encontrado']);
    exit;
}

// Get stats
// Total spent and order count
$stmt_stats = $conexion_store->prepare("SELECT COUNT(*) as total_orders, SUM(valor_compra) as total_spent 
                                        FROM compras_por_usuarios 
                                        WHERE usuario_id = ? AND estado = 'entregada' AND deleted_at IS NULL");
$stmt_stats->bind_param("i", $userId);
$stmt_stats->execute();
$stats = $stmt_stats->get_result()->fetch_assoc();

$user['total_orders'] = (int)$stats['total_orders'];
$user['total_spent'] = (float)($stats['total_spent'] ?? 0);

// Last 5 orders
$stmt_orders = $conexion_store->prepare("SELECT id, valor_compra as total, fecha, estado 
                                         FROM compras_por_usuarios 
                                         WHERE usuario_id = ? AND deleted_at IS NULL 
                                         ORDER BY fecha DESC LIMIT 5");
$stmt_orders->bind_param("i", $userId);
$stmt_orders->execute();
$res_orders = $stmt_orders->get_result();
$last_orders = [];
while ($row = $res_orders->fetch_assoc()) {
    $last_orders[] = $row;
}
$user['last_orders'] = $last_orders;

echo json_encode(['success' => true, 'customer' => $user]);
?>
