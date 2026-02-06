<?php
require_once('../core/db.php');
require_once('../core/session.php');
require_once('../core/_tasas_cambio.php');
require_once('../core/_calculadrora_precios.php');
// Función para optimizar imágenes y generar WebP
function optimizeImage($srcPath, $destPath, $maxWidth = 400, $quality = 75) {
    if (!file_exists($srcPath)) return false;

    $info = getimagesize($srcPath);
    if (!$info) return false;

    list($width, $height) = $info;
    $mime = $info['mime'];

    $ratio = $width / $height;
    $newWidth = $maxWidth;
    $newHeight = intval($maxWidth / $ratio);

    $image = null;
    switch ($mime) {
        case 'image/jpeg': $image = imagecreatefromjpeg($srcPath); break;
        case 'image/png':  $image = imagecreatefrompng($srcPath); break;
        case 'image/webp': $image = imagecreatefromwebp($srcPath); break;
        default: return false;
    }

    $thumb = imagecreatetruecolor($newWidth, $newHeight);

    if ($mime === 'image/png') {
        imagealphablending($thumb, false);
        imagesavealpha($thumb, true);
    }

    imagecopyresampled($thumb, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

    imagewebp($thumb, $destPath, $quality);

    imagedestroy($image);
    imagedestroy($thumb);

    return true;
}


header('Content-Type: application/json');

$user_id = getUserId();


function recompensas($user_id) {
    global $conexion_store;

    $recompensa = [];

   
    $sql = "SELECT porcentaje, monto, tipo FROM recompensas_usuario WHERE usuario_id = ?  AND estado = 'disponible'";
    $stmt = $conexion_store->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $porcentaje_descuento = (float) $row['porcentaje'] ?? 0;
            $tipo_descuento = $row['tipo'] ?? 'porcentaje';
            $descuento = $tipo_descuento === 'porcentaje' ? $porcentaje_descuento : $row['monto'];
            $recompensa[] = [
                'porcentaje' => $porcentaje_descuento,
                'tipo' => $tipo_descuento,
                'descuento' => $descuento
            ];
        }
    }
    return $recompensa;
}


 if (!isLoggedIn()) {
        $recompensas = [];
    }else{
        $recompensas = recompensas($user_id);
    }



// Initialize dependencies
$calculadora = new CalculadoraPrecios($pesoDolar, $peso_bolivar, $dolarBolivar, $bolivar_peso, $bcv, $data_monedas, $recompensas);
$sucursal = $_SESSION['sucursal'] ?? 9;
$bss_id = $_SESSION['bss_id'] ?? 3;

$mode = $_GET['mode'] ?? 'grid';

if ($mode === 'search_index') {
    // Lightweight query for all products (for Fuse.js)
    $sql = "SELECT p.id, p.nombre, p.codigo_barras, p.precio_compra, p.cantidad_unidades, p.origen, s.stock, s.porcentaje, p.mayor 
            FROM productos p
            INNER JOIN stock s ON p.id = s.id_producto
            WHERE p.activo = 0 AND s.id_sucursal = ? AND s.bss_id = ?  AND p.origen != 'c' AND s.stock > 0 
            ORDER BY p.nombre ASC";
    
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("ii", $sucursal, $bss_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $searchIndex = [];
    while ($row = $result->fetch_assoc()) {
        $precios = $calculadora->calcularPrecios($row);
        
        $nombre = mb_strtolower(trim($row['nombre']), 'UTF-8');
        $nombre = ucfirst($nombre);

        $searchIndex[] = [
            'id' => $row['id'],
            'n' => $nombre, // Short key for 'nombre' to save bandwidth
            'c' => trim($row['codigo_barras']), // 'codigo'
            's' => (int)$row['stock'], // 'stock'
            'm' => $row['mayor'], // 'mayor'
            'pd' => $precios['precio_venta_dolar'],
            'pp' => $precios['precio_venta_peso'],
            'pb' => $precios['precio_venta_bs'],
            'cd' => $precios['dolar_con_recompensa'],
            'cp' => $precios['bs_con_recompensa']
        ];
    }
    echo json_encode(['recompensas' => $recompensas, 'searchIndex' => $searchIndex]);
    exit;
}

// Default mode: Grid Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 24;
$sort = $_GET['order'] ?? '';
switch ($sort) {
    case 'name_asc':
        $order = 'p.nombre ASC';
        break;
    case 'name_desc':
        $order = 'p.nombre DESC';
        break;
    default:
        $order = 'p.id ASC';
        break;
}
$offset = ($page - 1) * $limit;

$sql = "SELECT p.*, s.stock, s.porcentaje, s.id_sucursal
        FROM productos p
        INNER JOIN stock s ON p.id = s.id_producto
        WHERE p.activo = 0 AND s.id_sucursal = ? AND s.bss_id = ? AND s.stock > 0 AND p.origen != 'c'
        ORDER BY {$order}
        LIMIT ? OFFSET ?";

$stmt = $conexion->prepare($sql);
$stmt->bind_param("iiii", $sucursal, $bss_id, $limit, $offset);
$stmt->execute();
$result = $stmt->get_result();

$products = [];
while ($row = $result->fetch_assoc()) {
    $precios = $calculadora->calcularPrecios($row);
    $valorUnidad = (float) $row['precio_compra'] / (float) $row['cantidad_unidades'];

    $nombre = mb_strtolower(trim($row['nombre']), 'UTF-8');
    $nombre = ucfirst($nombre);

    $imgPath = "../assets/img/stock/{$row['id']}.png";
    $optimizedPath = "../assets/img/stock/optimized/{$row['id']}.webp";
    if (!file_exists($optimizedPath) && file_exists($imgPath)) {
        optimizeImage($imgPath, $optimizedPath, 400, 75);
    }

    $img = '';
    if (file_exists($optimizedPath)) {
        $img = "assets/img/stock/optimized/{$row['id']}.webp";
    } elseif (file_exists($imgPath)) {
        $img = "";
    }


    $products[] = [
        'id' => $row['id'],
        'nombre' => $nombre,
        'stock' => (int)$row['stock'],
        'mayor' => $row['mayor'],
        'codigo' => trim($row['codigo_barras']),
        'precio_dolar_visible' => $precios['precio_venta_dolar'],
        'precio_peso_visible' => $precios['precio_venta_peso'],
        'precio_bs_visible' => $precios['precio_venta_bs'],
        'costo_dolar' => $precios['dolar_con_recompensa'],
        'costo_bs' => $precios['bs_con_recompensa'],
        'cantidadPaca' => $row['cantidad_unidades'],
        'img' => $img
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
    'recompensas' => $recompensas,
    'page' => $page,
    'limit' => $limit,
    'total' => (int)$total,
    'hasMore' => ($offset + $limit) < $total,
    'data' => $products
]);