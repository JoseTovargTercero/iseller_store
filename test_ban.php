<?php
// Mock the session and admin login if needed, or just run the logic
require_once 'admin/config/db.php';

// Simulate the POST data
$userId = 1; // Change to a real ID if possible, or just test the query logic
$newStatus = 0;

$stmt = $conexion_store->prepare("UPDATE usuarios SET estado = ? WHERE id = ?");
if (!$stmt) {
    echo "Prepare failed: " . $conexion_store->error . "\n";
    exit;
}
$stmt->bind_param("ii", $newStatus, $userId);

if ($stmt->execute()) {
    echo "Update successful for ID $userId\n";
    echo "Rows affected: " . $stmt->affected_rows . "\n";
} else {
    echo "Execute failed: " . $stmt->error . "\n";
}
?>
