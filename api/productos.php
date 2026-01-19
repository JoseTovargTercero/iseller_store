<?php
require_once('../core/db.php');
require_once('../core/session.php');
require_once('../core/_tasas_cambio.php');
require_once('../core/_calculadrora_precios.php');

header('Content-Type: application/json');

// Initialize dependencies
$calculadora = new CalculadoraPrecios($pesoDolar, $peso_bolivar, $dolarBolivar, $bolivar_peso, $bcv, $data_monedas);
$sucursal = $_SESSION['sucursal'] ?? 9;
$bss_id = $_SESSION['bss_id'] ?? 3;

$mode = $_GET['mode'] ?? 'grid';

if ($mode === 'search_index') {
    // Lightweight query for all products (for Fuse.js)
    $sql = "SELECT p.id, p.nombre, p.codigo_barras, p.precio_compra, p.cantidad_unidades, p.origen, s.stock, s.porcentaje, p.mayor 
            FROM productos p
            INNER JOIN stock s ON p.id = s.id_producto
            WHERE p.activo = 0 AND s.id_sucursal = ? AND s.bss_id = ?  AND p.origen != 'c'";
    
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("ii", $sucursal, $bss_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $searchIndex = [];
    while ($row = $result->fetch_assoc()) {
        $precios = $calculadora->calcularPrecios($row);
        
        $searchIndex[] = [
            'id' => $row['id'],
            'n' => $row['nombre'], // Short key for 'nombre' to save bandwidth
            'c' => trim($row['codigo_barras']), // 'codigo'
            's' => (int)$row['stock'], // 'stock'
            'm' => $row['mayor'], // 'mayor'
            'pd' => $precios['precio_venta_dolar'],
            'pp' => $precios['precio_venta_peso'],
            'pb' => $precios['precio_venta_bs']
        ];
    }
    echo json_encode($searchIndex);
    exit;
}

// Default mode: Grid Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 24;
$offset = ($page - 1) * $limit;

$sql = "SELECT p.*, s.stock, s.porcentaje, s.id_sucursal
        FROM productos p
        INNER JOIN stock s ON p.id = s.id_producto
        WHERE p.activo = 0 AND s.id_sucursal = ? AND s.bss_id = ? AND p.origen != 'c'
        ORDER BY p.id ASC
        LIMIT ? OFFSET ?";

$stmt = $conexion->prepare($sql);
$stmt->bind_param("iiii", $sucursal, $bss_id, $limit, $offset);
$stmt->execute();
$result = $stmt->get_result();

$products = [];
while ($row = $result->fetch_assoc()) {
    $precios = $calculadora->calcularPrecios($row);
    $valorUnidad = (float) $row['precio_compra'] / (float) $row['cantidad_unidades'];

    $products[] = [
        'id' => $row['id'],
        'nombre' => $row['nombre'],
        'stock' => (int)$row['stock'],
        'mayor' => $row['mayor'],
        'codigo' => trim($row['codigo_barras']),
        'precio_dolar_visible' => $precios['precio_venta_dolar'],
        'precio_peso_visible' => $precios['precio_venta_peso'],
        'precio_bs_visible' => $precios['precio_venta_bs'],
        'price_C' => $valorUnidad,
        'price_C_Bs' => $valorUnidad * $dolarBolivar,
        'price_C_Cop' => $valorUnidad * $pesoDolar,
        'cantidadPaca' => $row['cantidad_unidades']
    ];
}

$countSql = "
SELECT COUNT(*) total
FROM productos p
INNER JOIN stock s ON p.id = s.id_producto
WHERE p.activo = 0 AND s.id_sucursal = ? AND s.bss_id = ? AND p.origen != 'c'
";

$countStmt = $conexion->prepare($countSql);
$countStmt->bind_param("ii", $sucursal, $bss_id);
$countStmt->execute();
$total = $countStmt->get_result()->fetch_assoc()['total'];


echo json_encode([
    'page' => $page,
    'limit' => $limit,
    'total' => (int)$total,
    'hasMore' => ($offset + $limit) < $total,
    'data' => $products
]);