<?php
// admin/api/get_products.php
header('Content-Type: application/json');
require_once dirname(__DIR__) . '/config/db.php';
require_once dirname(__DIR__) . '/includes/session.php';
require_once dirname(dirname(__DIR__)) . '/core/_tasas_cambio.php';
require_once dirname(dirname(__DIR__)) . '/core/_calculadrora_precios.php';

requireAdminLogin();

$calculadora = new CalculadoraPrecios($pesoDolar, $peso_bolivar, $dolarBolivar, $bolivar_peso, $bcv, $data_monedas);
$sucursal = 9; // Default sucursal per logic
$bss_id = 3;   // Default bss_id per logic

$search = $_GET['search'] ?? '';

$sql = "SELECT p.*, s.stock, GROUP_CONCAT(c.nombre SEPARATOR ', ') as categorias_nombres
        FROM productos p
        INNER JOIN stock s ON p.id = s.id_producto
        LEFT JOIN categorias_productos cp ON p.id = cp.id_producto
        LEFT JOIN categorias c ON cp.id_categoria = c.id AND c.activo = 1
        WHERE p.activo = 0 
          AND s.id_sucursal = ? 
          AND s.bss_id = ? 
          AND p.origen != 'c' 
          AND s.stock > 0";

$types = "ii";
$params = [$sucursal, $bss_id];

if ($search) {
    $sql .= " AND (p.nombre LIKE ? OR p.codigo_barras LIKE ?)";
    $types .= "ss";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$sql .= " GROUP BY p.id ORDER BY p.nombre ASC LIMIT 100"; // Limit to prevent overload

$stmt = $conexion->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$products = [];
while ($row = $result->fetch_assoc()) {
    $precios = $calculadora->calcularPrecios($row);
    
    // Check if image exists
    $imgName = $row['id'] . '.png';
    
    $products[] = [
        'id' => $row['id'],
        'nombre' => $row['nombre'],
        'codigo' => $row['codigo_barras'],
        'stock' => (int)$row['stock'],
        'categorias' => $row['categorias_nombres'] ?? '',
        'precio_usd' => $precios['precio_venta_dolar'],
        'precio_bs' => $precios['precio_venta_bs'],
        'img_cache_bust' => time() // Trick to force reload image after upload
    ];
}

echo json_encode(['success' => true, 'products' => $products]);
?>
