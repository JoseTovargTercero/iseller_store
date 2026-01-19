<?php
// admin/config/db.php
// Reutilizamos la conexión global del proyecto
// Aseguramos que la ruta sea correcta dependiendo de dónde se incluya este archivo

$path = dirname(__DIR__, 2) . '/core/db.php';
if (file_exists($path)) {
    require_once $path;
} else {
    die("Error: No se encuentra la configuración de base de datos en core/db.php");
}
?>
