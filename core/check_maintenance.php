<?php
// core/check_maintenance.php

// Ensure database connection is available
require_once __DIR__ . '/db.php';

// Check maintenance status
$check_query = "SELECT mantenimiento, configuracion_hasta FROM configuracion WHERE configuracion = 'mantenimiento' LIMIT 1";
$check_result = $conexion_store->query($check_query);

if ($check_result && $check_result->num_rows > 0) {
    $check_row = $check_result->fetch_assoc();
    
    // If maintenance is active (1)
    if ($check_row['mantenimiento'] == 1) {
        $current_script = basename($_SERVER['PHP_SELF']);
        
        // If not already on maintenance page, redirect
        if ($current_script !== 'mantenimiento.php') {
            header("Location: mantenimiento.php");
            exit();
        }
        
        // Make the date available if included
        $maintenance_end = $check_row['configuracion_hasta'];
    } else {
        // If maintenance is NOT active, but we are ON the maintenance page, redirect to home
         $current_script = basename($_SERVER['PHP_SELF']);
         if ($current_script === 'mantenimiento.php') {
            header("Location: index.php");
            exit();
        }
    }
}
?>
