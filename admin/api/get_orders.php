<?php
// admin/api/get_orders.php
header('Content-Type: application/json');
require_once dirname(__DIR__) . '/config/db.php';
require_once dirname(__DIR__) . '/includes/session.php';

requireAdminLogin();

$status = $_GET['status'] ?? '';
$search = $_GET['search'] ?? '';


function getProductName($productId) {
    global $conexion;
    $stmt = $conexion->prepare("SELECT nombre FROM productos WHERE id = ?");
    $stmt->bind_param("i", $productId);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();
    return $result->fetch_assoc()['nombre'];
}

// Construir Query Principal
// Usamos compras_por_usuarios como base para el status del admin
$sql = "
    SELECT 
        cpu.id as cpu_id, 
        cpu.compra_id as orden_id, 
        cpu.estado, 
        cpu.fecha as fecha_compra,
        cpu.valor_compra as total,
        cpu.valor_compra_bs,
        cpu.ahorrado,
        cpu.numero_operacion_bancaria,
        cpu.hora_operacion_bancaria,
        cpu.ahorrado_bs,
        cpu.importe_envio,
        cpu.motivo_rechazo,
        cpu.tipo_entrega,
        cpu.importe_envio_bs,
        u.nombre as cliente_nombre,
        u.email as cliente_email,
        u.telefono as cliente_telefono,
        ud.direccion as direccion_entrega,
        ud.referencia,
        ud.nombre_receptor,
        ud.telefono as telefono_receptor,
        ud.lat,
        ud.lng
    FROM compras_por_usuarios cpu
    JOIN usuarios u ON cpu.usuario_id = u.id
    LEFT JOIN usuarios_direcciones ud ON cpu.direccion_id = ud.id
    WHERE cpu.deleted_at IS NULL
";

$types = "";
$params = [];

if ($status) {
    $sql .= " AND cpu.estado = ?";
    $types .= "s";
    $params[] = $status;
} else {
    // Regla: No mostrar entregadas por defecto si no se pide explicitamente (o filtro 'todos' pero la regla dice 'solo diferentes de entregada' en lista principal)
    // Asumiremos que si no se envia status, traemos todas MENOS entregada y rechazada para el view principal, o el frontend maneja los tabs.
    // El requerimiento dice: "El panel SOLO debe mostrar compras cuyo estado sea DIFERENTE de entregada".
    // Haremos el filtro estricto aquí si no viene parametro.
    $sql .= " AND cpu.estado != 'entregada'";
}

if ($search) {
    $sql .= " AND (u.nombre LIKE ? OR cpu.compra_id LIKE ?)";
    $types .= "ss";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$sql .= " ORDER BY cpu.fecha DESC";

$stmt = $conexion_store->prepare($sql);
if ($types) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$res = $stmt->get_result();

$orders = [];
while ($row = $res->fetch_assoc()) {
    // Obtener productos de la orden
    $ordenId = $row['orden_id'];
    $sqlItems = "
        SELECT 
            oa.product_id,
            oa.quantity, 
            oa.precio_venta_dolar as precio
        FROM orden_articulos oa
        WHERE oa.order_id = ?
    ";
    $stmtItems = $conexion_store->prepare($sqlItems); // Nota: items estÃ¡n en DB principal $conexion
    $stmtItems->bind_param("i", $ordenId);
    $stmtItems->execute();
    $resItems = $stmtItems->get_result();
    
    $items = [];
    while ($item = $resItems->fetch_assoc()) {
        $item['subtotal'] = $item['quantity'] * $item['precio'];
        $item['producto_nombre'] = getProductName($item['product_id']);
        $items[] = $item;
    }
    $row['items'] = $items;
    
    // Normalizar datos
    $row['cliente'] = [
        'nombre' => $row['cliente_nombre'],
        'email' => $row['cliente_email'],
        'telefono' => $row['cliente_telefono']
    ];
    $row['operacion'] = [
        'numero' => $row['numero_operacion_bancaria'],
        'hora' => $row['hora_operacion_bancaria']
    ];
    $row['entrega'] = [
        'tipo' => $row['tipo_entrega'],
        'direccion' => $row['direccion_entrega'] ?? 'Retiro en tienda', // Fallback visual
        'referencia' => $row['referencia'] ?? '',
        'receptor' => $row['nombre_receptor'] ?? '',
        'lat' => $row['lat'],
        'lng' => $row['lng']
    ];
    
    $orders[] = $row;
    $stmtItems->close();
}

echo json_encode(['success' => true, 'orders' => $orders]);
?>
