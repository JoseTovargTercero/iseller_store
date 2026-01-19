<?php
// admin/api/get_customers.php
header('Content-Type: application/json');
require_once dirname(__DIR__) . '/config/db.php';
require_once dirname(__DIR__) . '/includes/session.php';

requireAdminLogin();

$search = $_GET['search'] ?? '';
$search = "%$search%";

$sql = "SELECT id, nombre, email, telefono, nivel, estado, creado_en 
        FROM usuarios 
        WHERE (nombre LIKE ? OR email LIKE ? OR telefono LIKE ?)
        ORDER BY creado_en DESC";

$stmt = $conexion_store->prepare($sql);
$stmt->bind_param("sss", $search, $search, $search);
$stmt->execute();
$result = $stmt->get_result();

$customers = [];
while ($row = $result->fetch_assoc()) {
    $row['estado'] = (int)$row['estado'];
    $row['nivel'] = (int)$row['nivel'];
    $customers[] = $row;
}

echo json_encode([
    'success' => true,
    'customers' => $customers
]);
?>
