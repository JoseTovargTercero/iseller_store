<?php
// api/get_categories_list.php
header('Content-Type: application/json');
require_once('../core/db.php');

$sql = "SELECT id, nombre FROM categorias WHERE activo = 1 ORDER BY nombre ASC";
$result = $conexion->query($sql);

$categories = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $categories[] = $row;
    }
}

echo json_encode(['success' => true, 'categories' => $categories]);
?>
