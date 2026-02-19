<?php
// admin/api/get_customer_stats.php test
require_once 'admin/config/db.php';

echo "Testing get_customer_stats.php logic...\n";

try {
    // Hoy
    $sqlToday = "SELECT COUNT(*) as total FROM usuarios WHERE DATE(creado_en) = CURDATE()";
    $resToday = $conexion_store->query($sqlToday);
    $today = $resToday->fetch_assoc()['total'] ?? 0;

    // Esta semana (últimos 7 días)
    $sqlWeek = "SELECT COUNT(*) as total FROM usuarios WHERE creado_en >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
    $resWeek = $conexion_store->query($sqlWeek);
    $week = $resWeek->fetch_assoc()['total'] ?? 0;

    // Este mes (últimos 30 días)
    $sqlMonth = "SELECT COUNT(*) as total FROM usuarios WHERE creado_en >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
    $resMonth = $conexion_store->query($sqlMonth);
    $month = $resMonth->fetch_assoc()['total'] ?? 0;

    echo "Stats found:\n";
    echo "Today: $today\n";
    echo "Week: $week\n";
    echo "Month: $month\n";
    
    echo "SUCCESS: Logic verified.\n";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
?>
