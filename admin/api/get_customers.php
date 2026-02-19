<?php
// admin/api/get_customers.php
header('Content-Type: application/json');
require_once dirname(__DIR__) . '/config/db.php';
require_once dirname(__DIR__) . '/includes/session.php';

requireAdminLogin();

$search = $_GET['search'] ?? '';
$search_param = "%$search%";
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
$offset = ($page - 1) * $limit;

// 1. Contar total de registros para la búsqueda
$sqlCount = "SELECT COUNT(*) as total FROM usuarios WHERE (nombre LIKE ? OR email LIKE ? OR telefono LIKE ?)";
$stmtCount = $conexion_store->prepare($sqlCount);
$stmtCount->bind_param("sss", $search_param, $search_param, $search_param);
$stmtCount->execute();
$totalRecords = $stmtCount->get_result()->fetch_assoc()['total'];

// 2. Obtener registros paginados
$sql = "SELECT id, nombre, email, telefono, nivel, estado, creado_en 
        FROM usuarios 
        WHERE (nombre LIKE ? OR email LIKE ? OR telefono LIKE ?)
        ORDER BY creado_en DESC
        LIMIT ? OFFSET ?";

$stmt = $conexion_store->prepare($sql);
$stmt->bind_param("sssii", $search_param, $search_param, $search_param, $limit, $offset);
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
    'customers' => $customers,
    'pagination' => [
        'total' => (int)$totalRecords,
        'page' => $page,
        'limit' => $limit,
        'pages' => ceil($totalRecords / $limit)
    ]
]);
?>
