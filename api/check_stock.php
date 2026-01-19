<?php
// api/check_stock.php
header('Content-Type: application/json');
require_once('../core/db.php');
require_once('../core/session.php');

if (!isset($_GET['id']) || !isset($_GET['cantidad'])) {
    echo json_encode(['success' => false, 'message' => 'Faltan parámetros']);
    exit;
}

$id = (int)$_GET['id'];
$cantidad = (float)$_GET['cantidad'];

// Configuración de sucursal por defecto (igual que en productos.php)
$sucursal = $_SESSION['sucursal'] ?? 9;
$bss_id = $_SESSION['bss_id'] ?? 3;

// Verificar si es producto "mayorista" (siempre tiene stock o lógica diferente?)
// Asumiremos lógica estándar de stock tabla 'stock'
// Nota: en productos.php se filtra por "p.activo = 0 AND s.id_sucursal = ? AND s.bss_id = ? AND p.origen != 'c'"
// Vamos a verificar solo la cantidad disponible en la tabla stock.

$stmt = $conexion->prepare("SELECT stock, id_producto FROM stock WHERE id_producto = ? AND id_sucursal = ?");
$stmt->bind_param("ii", $id, $sucursal);
$stmt->execute();
$res = $stmt->get_result();

if ($row = $res->fetch_assoc()) {
    $stockActual = (float)$row['stock'];
    
    // También verificar si es 'mayor'. si es 'mayor' == 1, la lógica de productos.php a veces ignora stock o lo muestra diferente.
    // Pero la solicitud pide "verificar en stock la disponibilidad".
    // Si el usuario quiere verificar stock real, comparamos.
    
    if ($stockActual >= $cantidad) {
        echo json_encode(['success' => true, 'stock' => $stockActual]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Stock insuficiente', 'current_stock' => $stockActual]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Producto no encontrado en inventario']);
}
?>
