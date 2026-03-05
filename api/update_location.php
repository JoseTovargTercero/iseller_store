<?php
require_once('../core/db.php');
require_once('../core/session.php');

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

$user_id = getUserId();
$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
    exit;
}

$id = isset($data['id']) ? intval($data['id']) : null;
$direccion = $data['direccion'] ?? '';
$referencia = $data['referencia'] ?? '';
$lat = $data['lat'] ?? null;
$lng = $data['lng'] ?? null;
$comunidad = $data['comunidad'] ?? null;
$nombre_receptor = $data['nombre_receptor'] ?? '';
$telefono = $data['telefono'] ?? '';
$delivery_gratis_confirmado = isset($data['delivery_gratis_confirmado']) ? intval($data['delivery_gratis_confirmado']) : 0;

if (empty($direccion) || $lat === null || $lng === null) {
    echo json_encode(['success' => false, 'message' => 'Faltan datos obligatorios']);
    exit;
}
if ($id) {
    $stmt = $conexion_store->prepare("
        UPDATE usuarios_direcciones 
        SET direccion = ?, referencia = ?, lat = ?, lng = ?, comunidad = ?, delivery_gratis_confirmado = ?, nombre_receptor = ?, telefono = ? 
        WHERE id = ? AND usuario_id = ?
    ");
    $stmt->bind_param("ssddsisssi", $direccion, $referencia, $lat, $lng, $comunidad, $delivery_gratis_confirmado, $nombre_receptor, $telefono, $id, $user_id);
} else {
    // Nueva ubicación
    // Primero, si es la primera ubicación, marcar como principal
    $stmtCheck = $conexion_store->prepare("SELECT COUNT(*) FROM usuarios_direcciones WHERE usuario_id = ?");
    $stmtCheck->bind_param("i", $user_id);
    $stmtCheck->execute();
    $resultCheck = $stmtCheck->get_result();
    $count = $resultCheck->fetch_row()[0];
    $stmtCheck->close();
    
    $es_principal = ($count === 0) ? 1 : 0;

    $stmt = $conexion_store->prepare("
        INSERT INTO usuarios_direcciones (usuario_id, direccion, referencia, lat, lng, comunidad, delivery_gratis_confirmado, es_principal, nombre_receptor, telefono) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("issddssiss", $user_id, $direccion, $referencia, $lat, $lng, $comunidad, $delivery_gratis_confirmado, $es_principal, $nombre_receptor, $telefono);
}

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Ubicación guardada correctamente']);
} else {
    echo json_encode(['success' => false, 'message' => 'Error al guardar: ' . $stmt->error]);
}

$stmt->close();
?>
