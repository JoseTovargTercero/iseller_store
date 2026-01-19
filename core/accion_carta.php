<?php
// Inicialización
include 'la-carta.php';
$cart = new Cart;

require_once('db.php');
require_once('session.php');
require("_tasas_cambio.php");
require("_calculadrora_precios.php");

$calculadora = new CalculadoraPrecios($pesoDolar, $peso_bolivar, $dolarBolivar, $bolivar_peso, $bcv, $data_monedas);

$desscontado = '';

$id_sucursal = $_SESSION["sucursal"];
$bss_id = $_SESSION["bss_id"];

// Obtener configuración del sistema

// Funciones auxiliares
function diaSemana($fecha)
{
    return date('N', strtotime($fecha));
}

function semanaAno($fecha)
{
    return date('W', strtotime($fecha));
}

// Validar acción
if (isset($_REQUEST['action']) && !empty($_REQUEST['action'])) {
    $action = $_REQUEST['action'];

    switch ($action) {
        case 'enviarPedidos':
            procesarCarritos();
            break;

        default:
            echo json_encode(['status' => false, 'data' => 'No se ha especificado una acción']);
    }
} else {
    echo json_encode(['status' => false, 'data' => 'No se ha especificado una acción']);
}

// -----------------------------------------------------------
// FUNCIONES
// -----------------------------------------------------------

function procesarCarritos()
{
    $pedidos = $_REQUEST['pedidos'];
    global $conexion;
    // recorre pedidos, cada pedido es un carrito. que posee un 'metodoPago', despacho (puede se placeOrder o placeOrderCredito), y un array de productos
    $pedidos = json_decode($pedidos, true);
    if (empty($pedidos)) {
        echo json_encode(['status' => false, 'data' => 'No hay pedidos para procesar']);
        return;
    }

    $errores = [];

    foreach ($pedidos as $pedido) {
        $metodoPago = str_replace('option', '', $pedido['metodoPago']);
        $valorFinalBs = $pedido['valorFinalBs'];
        $valorFinalCop = $pedido['valorFinalCop'];
        $despacho = $pedido['despacho'];
        $productos = $pedido['productos'];
        $idPedido = $pedido['id'];
        $cliente = $pedido['datosCliente'] ?? []; // Cliente puede ser un array vacío si no se proporciona información

        if (empty($productos)) {
            $errores[] = "El pedido con método de pago '$metodoPago' no tiene productos.";
            continue;
        }

        // Crear un nuevo carrito
        $cart = new Cart;

        // Validar y Actualizar precios con DB
        $productosValidados = [];
        $valorFinalVenta = 0;
        $valorFinalBs = 0;
        $valorFinalCop = 0;

        foreach ($productos as $prodFrontend) {
            $idProd = (int)$prodFrontend['id'];
            
            // Consultar datos reales
            $sqlProd = "SELECT p.*, s.stock, s.porcentaje 
                        FROM productos p 
                        INNER JOIN stock s ON p.id = s.id_producto 
                        WHERE p.id = ? AND s.id_sucursal = ? AND s.bss_id = ? LIMIT 1";
            
            $stmtP = $conexion->prepare($sqlProd);
            $stmtP->bind_param("iii", $idProd, $id_sucursal, $bss_id);
            $stmtP->execute();
            $resP = $stmtP->get_result();
            
            if ($resP->num_rows === 0) {
                 continue; // Producto no existe o no disponible en sucursal
            }
            
            $prodDB = $resP->fetch_assoc();
            $stmtP->close();

             // Validar Stock
             $qty = (float)$prodFrontend['qty'];
             // Si es venta al mayor, validar stock de bultos? (Lógica existente asumida correcta, pero se puede reforzar)
             if ($qty > $prodDB['stock'] && $prodDB['mayor'] != '1') {
                 $errores[$idPedido] = "Stock insuficiente para el producto: " . $prodDB['nombre'];
                 break; 
             }

             // Recalcular Precios
             $precios = $calculadora->calcularPrecios($prodDB);
             $valorUnidad = (float) $prodDB['precio_compra'] / (float) $prodDB['cantidad_unidades'];

             // Sobreescribir datos del frontend con los calculados
             $prodFrontend['price'] = $precios['precio_venta_dolar'];
             $prodFrontend['pricePeso'] = $precios['precio_venta_peso'];
             $prodFrontend['priceBolivar'] = $precios['precio_venta_bs'];
             
             $prodFrontend['price_C'] = $valorUnidad;
             $prodFrontend['price_C_Bs'] = $valorUnidad * $dolarBolivar;
             $prodFrontend['price_C_Cop'] = $valorUnidad * $pesoDolar;

             // Acumular totales reales
             $valorFinalVenta += $prodFrontend['price'] * $qty;
             $valorFinalBs += $prodFrontend['priceBolivar'] * $qty;
             $valorFinalCop += $prodFrontend['pricePeso'] * $qty;

             agregarAlCarrito($cart, $prodFrontend);
        }

        if (isset($errores[$idPedido])) {
            $cart->destroy();
            continue;
        }

        // Procesar la orden según el método de pago
        $tipoVeta = ($despacho == '2' ? 'credito' : 'contado'); // Credito/contado

        $respuesta = procesarOrden(
            $conexion,
            $cart,
            $tipoVeta,
            $despacho,
            $metodoPago,
            $valorFinalBs,
            $valorFinalCop,
            $cliente
        );
        if (!$respuesta['status']) {
            $errores[$idPedido] = $respuesta['data'];
        }

        $cart->destroy();
    }
    if (empty($errores)) {
        echo json_encode(['status' => true, 'data' => 'Todos las vetnas se procesaron correctamente.']);
    } else {
        echo json_encode(['status' => false, 'data' => $errores]);
    }
}



function agregarAlCarrito($cart, $producto)
{
    $mayor = $producto['mayor'] == 'undefined' ? '0' : ($producto['mayor'] ?? 0);

    $itemData = [
        'id' => $producto['id'],
        'name' => $producto['name'],
        'price_C' => $producto['price_C'],
        'price_C_Bs' => $producto['price_C_Bs'],
        'price_C_Cop' => $producto['price_C_Cop'],
        'price' => floatval($producto['price']),
        'pricePeso' => floatval($producto['pricePeso']),
        'priceBolivar' => floatval($producto['priceBolivar']),
        'qty' => $producto['qty'],
        'mayor' => $mayor,
        'cantidadPaca' => $producto['cantidadPaca']
    ];

    $cart->insert($itemData);
}


function es_venta_mayor($cart)
{
    $result = false;

    foreach ($cart->contents() as $item) {
        if ($item['mayor'] == '1') {
            $result = true;
        }
    }
    return $result;
}

/* * Procesa la orden de compra, ya sea al contado o a crédito.
 * @param object $conexion Conexión a la base de datos.
 * @param object $cart Objeto del carrito de compras.
 * @param string $tipoVenta Tipo de venta ( 1 es venta normal. 2 es credito, 3 es descuento, 4 es venta al mayor ).
 * @param int $compraTipo Tipo de compra (1 para detal, 2 para mayor).
 * @param int $pagoTipo Tipo de pago (Punto, biopago, pesos, etc).
 * @param float $precioBs Precio en bolívares.
 * @param float $precioCop Precio en pesos colombianos.
 */

function procesarOrden($conexion, $cart, $tipo = 'contado', $tipoVenta = 1, $pagoTipo = 0, $precioBs = 0, $precioCop = 0, $cliente = [])
{
    global $id_sucursal, $bss_id;

    if ($cart->total_items() <= 0 || empty($_SESSION['id'])) {
        echo json_encode(['status' => false, 'data' => 'No hay productos en el carrito']);
        return;
    }

    $conexion_store->begin_transaction();

    try {
        // Datos base
        $fechaVenta = date('Y-m-d');
        $precioCop = formatPeso($precioCop ?? 0);

        $valorFinalVenta = $cart->total();
        $compraTipo = 1; // Detal por defecto
        if ($tipo == 'credito') {
            $tipoVenta = 2;
        }

        // verifica el $cart, si hay algun producto al mayor, el statuV pasa a ser 4
        if (es_venta_mayor($cart)) {
            $compraTipo = 4; // Venta al mayor
            if ($tipo == 'credito') {
                $pagoTipo = 4;
            } else {
                $tipoVenta = 4;
            }
        }


        $mes = date('Y-m');
        $ano = date('Y');
        $semana = date('Y-W');
        $dia = date('N');

        // Registrar orden
        $stmt = $conexion_store->prepare("
        INSERT INTO orden (
            status, customer_id, total_price, created, modified, fecha,
            semana, ano, total_price_bs, total_price_cop, tipoPago,
            dia, id_sucursal, bss_id
        ) VALUES (?, ?, ?, NOW(), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

        if (!$stmt) {
            throw new Exception("Error al preparar la consulta: " . $conexion_store->error);
        }

        $stmt->bind_param(
            "iisssssddsiii",
            $tipoVenta,
            $_SESSION['id'],
            $valorFinalVenta,
            $fechaVenta,
            $mes,
            $semana,
            $ano,
            $precioBs,
            $precioCop,
            $pagoTipo,
            $dia,
            $id_sucursal,
            $bss_id
        );

        if (!$stmt->execute()) {
            $error = $stmt->error;
            $stmt->close();
            throw new Exception("Error al ejecutar la inserción en orden: " . $error);
        }

        $orderID = $stmt->insert_id;
        $stmt->close();

        if ($orderID <= 0) {
            throw new Exception("No se pudo obtener el ID de la orden insertada.");
        }


        // Guardar artículos
        guardarArticulosOrden($conexion_store, $cart, $orderID);

      
        $conexion_store->commit(); // Éxito

        $cart->destroy();

        return ['status' => true];
        echo json_encode(['status' => true, 'data' => $msg, 'id' => $orderID]);
    } catch (Exception $e) {
        $conexion_store->rollback();
        return ['status' => false, 'data' => 'Error al procesar la orden: ' . $e->getMessage()];
    }
}



function guardarArticulosOrden($conexion_store, $cart, $orderID)
{
    global $dolarBolivar, $pesoDolar, $id_sucursal, $bss_id, $conexion;

    // Preparar consulta de inserción para orden_articulos
    $insertStmt = $conexion_store->prepare(
        "INSERT INTO orden_articulos (
            order_id, product_id, quantity, precio, bolivar, peso,
            precio_venta_dolar, precio_venta_bs, precio_venta_cop, id_sucursal, bss_id
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );

    // Preparar consultas para stock
    $stmtStock  = $conexion->prepare("SELECT stock, id_stock FROM stock WHERE id_producto = ? AND id_sucursal = ? AND bss_id = ? LIMIT 1");
    // para obtener la cantidad actual
    $updateStmt = $conexion->prepare("UPDATE stock SET stock = ? WHERE id_producto = ? AND id_sucursal = ? AND bss_id = ?");
    $updateStmtMayor = $conexion->prepare("UPDATE stock SET stock = ? WHERE id = ? AND id_sucursal = ? AND bss_id = ?");
    // para actualizar la cantidad actual
    $stmtStockParaMayor  = $conexion->prepare("SELECT stock FROM stock WHERE id = ? AND id_sucursal = ? AND bss_id = ? LIMIT 1");


    foreach ($cart->contents() as $item) {

        // Ejecutar inserción del artículo de la orden
        $insertStmt->bind_param(
            "iiddddddddi",
            $orderID,
            $item['id'],
            $item['qty'],
            $item['price_C'],
            $item['price_C_Bs'],
            $item['price_C_Cop'],
            $item['price'],
            $item['priceBolivar'],
            $item['pricePeso'],
            $id_sucursal,
            $bss_id
        );
        $insertStmt->execute();



        if ($item['mayor'] == '1') {

            $stmtStock->bind_param("iii", $item['id'], $id_sucursal, $bss_id);
            $stmtStock->execute();
            $result = $stmtStock->get_result()->fetch_assoc();
            $id_stock = $result['id_stock'];



            $stmtStockParaMayor->bind_param("iii", $id_stock, $id_sucursal, $bss_id);
            $stmtStockParaMayor->execute();
            $result = $stmtStockParaMayor->get_result()->fetch_assoc();
            $stock = max(0, $result['stock'] - ($item['qty'] * $item['cantidadPaca']));
            // se debe multiplicar por la cantidad




            $updateStmtMayor->bind_param("iiii", $stock, $id_stock, $id_sucursal, $bss_id);
            $updateStmtMayor->execute();
        } else {

            // Actualizar stock
            $stmtStock->bind_param("iii", $item['id'], $id_sucursal, $bss_id);
            $stmtStock->execute();
            $result = $stmtStock->get_result()->fetch_assoc();
            $stock = max(0, $result['stock'] - $item['qty']);


            $updateStmt->bind_param("iiii", $stock, $item['id'], $id_sucursal, $bss_id);
            $updateStmt->execute();
        }
    }

    // Cerrar todas las sentencias
    $insertStmt->close();
    $stmtStock->close();
    $updateStmt->close();
    $updateStmtMayor->close();
}
