<?php
require_once('../core/db.php');
require_once('../core/session.php');
require_once('../core/_tasas_cambio.php');
require_once('../core/_calculadrora_precios.php');
$mode = $_GET['mode'] ?? 'grid';
$searchIndex = [];



$user_id = getUserId();




echo '<table> 
    <tr>
        <th>ID</th>
        <th>Nombre</th>
        <th>Stock</th>
        <th>Mayor</th>
    </tr>
    ';


    function consultarStockMayor() : int {
        global $conexion;
        $sql = "SELECT stock FROM stock WHERE id_producto = ? AND id_sucursal = ? AND bss_id = ?";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("iii", $id_producto, $id_sucursal, $bss_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            return $row['stock'];
        }
        return 0;
    }

/* Productos de socios */
$sucursal_socio = 8;
$bss_id_socio = 2;

// $cambio es la clase TasasCambio
$respuesta_socio = $cambio->obtenerCambio($bss_id_socio);
$tasas_socio = json_decode($respuesta_socio, true); // true = array asociativo

$tasaMostradas_socio = $cambio->tasasMostradas($bss_id_socio);
$tasaMostradas_socio = json_decode($tasaMostradas_socio, true);
$data_monedas_socio = $tasaMostradas_socio['data'];

$pesoDolar_socio = $tasas_socio['data']['pesoDolar'];
$peso_bolivar_socio = $tasas_socio['data']['peso_bolivar'];
$bolivar_peso_socio = $tasas_socio['data']['bolivar_peso'];
$dolarBolivar_socio = $tasas_socio['data']['DolarBolivar'];
$bsDolar_socio = $dolarBolivar_socio;
$bcv_socio =  $tasas_socio['data']['bcv'];
$tipo_tasa_bs_socio = $tasas_socio['data']['tipo_tasa_bs'];
$redondeo_socio = $tasas_socio['data']['redondeo'];
$margen_neto_socio = $tasas_socio['data']['margen_neto'];
// Informacion de la tipo de cambio estandar
$calculadora_socio = new CalculadoraPrecios($pesoDolar_socio, $peso_bolivar_socio, $dolarBolivar_socio, $bolivar_peso_socio, $bcv_socio, $data_monedas_socio, []);

$respuesta = [];
$loreamny_productos = "(4584, 4789, 5330, 4087, 4362, 4796, 4798, 4660, 4102"; // mayor
$loreamny_productos = "1658, 2874, 1628, 2872, 1659, 3803, 3805, 1652, 4097, 1652, 1651, 3806, 1684, 1627)"; // mayor


    // Lightweight query for all products (for Fuse.js)
    $sql = "SELECT p.id, p.nombre, p.codigo_barras, p.precio_compra, p.cantidad_unidades, p.origen, s.stock, s.porcentaje, p.mayor, 
                   GROUP_CONCAT(c.nombre SEPARATOR ', ') as categorias_nombres
            FROM productos p
            INNER JOIN stock s ON p.id = s.id_producto
            LEFT JOIN categorias_productos cp ON p.id = cp.id_producto
            LEFT JOIN categorias c ON cp.id_categoria = c.id AND c.activo = 1
            WHERE (p.activo = 0 AND s.id_sucursal = ? AND s.bss_id = ? ) OR (p.activo = 0 AND s.id_sucursal = ? AND s.bss_id = ?  AND p.mayor = 1)
            GROUP BY p.id
            ORDER BY p.nombre ASC";
    
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("iiii", $sucursal_socio, $bss_id_socio, $sucursal_socio, $bss_id_socio);
    $stmt->execute();
    $result = $stmt->get_result();

    $searchIndex = [];
    while ($row = $result->fetch_assoc()) {
        $precios = $calculadora_socio->calcularPrecios($row);
        
        $nombre = mb_strtolower(trim($row['nombre']), 'UTF-8');
        $nombre = ucfirst($nombre);

        $stock = $row['stock'] > 0 ? $row['stock'] : consultarStockMayor($row['id'], $sucursal_socio, $bss_id_socio);


        echo '<tr>
            <td>' . $row['id'] . '</td>
            <td>' . $nombre . '</td>
            <td>' . (int)$stock . '</td>
            <td>' . $row['mayor'] . '</td>
        </tr>';
    }
 

echo '</table>';
/* Productos de socios */



