<?php
require_once 'core/db.php';

echo "Table: usuarios\n";
$result = $conexion_store->query("DESCRIBE usuarios");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        print_r($row);
    }
} else {
    echo "Error: " . $conexion_store->error;
}
?>
