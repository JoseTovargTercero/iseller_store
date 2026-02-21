<?php
// admin/api/categories.php
header('Content-Type: application/json');
require_once dirname(__DIR__) . '/config/db.php';
require_once dirname(__DIR__) . '/includes/session.php';

requireAdminLogin();

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

if ($method === 'GET') {
    // List categories
    $sql = "SELECT * FROM categorias WHERE activo = 1 ORDER BY nombre ASC";
    $result = $conexion->query($sql);
    $categories = [];
    while ($row = $result->fetch_assoc()) {
        $categories[] = $row;
    }
    echo json_encode(['success' => true, 'categories' => $categories]);
    exit;
}

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if ($action === 'create') {
        $nombre = $input['nombre'] ?? '';
        if (empty($nombre)) {
            echo json_encode(['success' => false, 'message' => 'Nombre requerido']);
            exit;
        }
        
        $stmt = $conexion->prepare("INSERT INTO categorias (nombre) VALUES (?)");
        $stmt->bind_param("s", $nombre);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'id' => $stmt->insert_id]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al crear categoría: ' . $stmt->error]);
        }
        exit;
    }
    
    if ($action === 'update') {
        $id = $input['id'] ?? null;
        $nombre = $input['nombre'] ?? '';
        
        if (!$id || empty($nombre)) {
            echo json_encode(['success' => false, 'message' => 'ID y nombre requeridos']);
            exit;
        }
        
        $stmt = $conexion->prepare("UPDATE categorias SET nombre = ? WHERE id = ?");
        $stmt->bind_param("si", $nombre, $id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al actualizar categoría: ' . $stmt->error]);
        }
        exit;
    }
    
    if ($action === 'delete') {
        $id = $input['id'] ?? null;
        
        if (!$id) {
            echo json_encode(['success' => false, 'message' => 'ID requerido']);
            exit;
        }
        
        // Soft delete
        $stmt = $conexion->prepare("UPDATE categorias SET activo = 0 WHERE id = ?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al eliminar categoría: ' . $stmt->error]);
        }
        exit;
    }
}

echo json_encode(['success' => false, 'message' => 'Acción no válida']);
?>
