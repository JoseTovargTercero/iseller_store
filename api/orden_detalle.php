<?php
require_once('../core/db.php');
require_once('../core/session.php');

header('Content-Type: application/json');

// 1. Verificar sesión
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

$user_id = getUserId();
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID de orden inválido']);
    exit;
}



function getProductName($productId) {
    global $conexion;
    $stmt = $conexion->prepare("SELECT nombre FROM productos WHERE id = ?");
    $stmt->bind_param("i", $productId);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();
    return $result->fetch_assoc()['nombre'];
}


try {
    // 2. Verificar que la orden pertenezca al usuario y obtener datos generales
    // Usamos compras_por_usuarios que tiene info de delivery, descuentos, etc. de esa transacción específica para el usuario
    $queryOrden = "
        SELECT 
            c.id, 
            c.fecha, 
            c.valor_compra, 
            c.importe_envio, 
            c.tipo_entrega, 
            c.ahorrado, 
            c.estatus,
            c.numero_operacion_bancaria,
            c.hora_operacion_bancaria,
            c.compra_id,
            d.direccion, 
            d.referencia
        FROM compras_por_usuarios c
        LEFT JOIN usuarios_direcciones d ON c.direccion_id = d.id
        WHERE c.id = ? AND c.usuario_id = ?
        LIMIT 1
    ";

    $stmt = $conexion_store->prepare($queryOrden);
    $stmt->bind_param("ii", $id, $user_id);
    $stmt->execute();
    $resOrden = $stmt->get_result();
    $orden = $resOrden->fetch_assoc();
    $stmt->close();

    if (!$orden) {
        throw new Exception("Orden no encontrada o no tienes permiso para verla.");
    }
    $compra_id = $orden['compra_id'];

    // 3. Obtener los artículos de la orden
    // La tabla orden_articulos linkea con productos
    // Nota: compramos_por_usuarios.compra_id DEBERÍA corresponder a orden.id. 
    // En checkout.php: $stmtRel->bind_param(..., $orden_id, ...) donde $orden_id es el id insertado en tabla `orden`.
    $queryItems = "
        SELECT 
            oa.quantity, 
            oa.precio_venta_dolar as precio,
            oa.id,
            oa.product_id
        FROM orden_articulos oa
        WHERE oa.order_id = ?
    ";
    
    // NOTA: Como orden_articulos está en la DB principal ($conexion) y compras_por_usuarios en $conexion_store,
    // debemos usar la conexión apropiada. 
    // db.php usualmente define $conexion (POS/Principal) y $conexion_store (Store/Usuarios).
    // orden_articulos suele estar en la DB del POS.
    
    $stmtItems = $conexion_store->prepare($queryItems);
    $stmtItems->bind_param("i", $compra_id);
    $stmtItems->execute();
    $resItems = $stmtItems->get_result();
    
    $items = [];
    while ($row = $resItems->fetch_assoc()) {
        $items[] = [
            'id' => $row['product_id'],
            'nombre' => getProductName($row['product_id']),
            'cantidad' => $row['quantity'],
            'precio' => floatval($row['precio']),
            'subtotal' => floatval($row['precio']) * intval($row['quantity'])
        ];
    }
    $stmtItems->close();

    // Responder
    echo json_encode([
        'success' => true,
        'orden' => [
            'id' => $orden['id'],
            'fecha' => $orden['fecha'],
            'estatus' => $orden['estatus'],
            'subtotal' => floatval($orden['valor_compra']), // Valor compra suele ser solo productos
            'envio' => floatval($orden['importe_envio']),
            'descuento' => floatval($orden['ahorrado']),
            'total' => floatval($orden['valor_compra']) + floatval($orden['importe_envio']) - floatval($orden['ahorrado']),
            'tipo_entrega' => $orden['tipo_entrega'],
            'direccion' => $orden['direccion'] ? $orden['direccion'] . ' (' . $orden['referencia'] . ')' : 'Retiro en tienda',
            'pago_ref' => $orden['numero_operacion_bancaria']
        ],
        'items' => $items
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
