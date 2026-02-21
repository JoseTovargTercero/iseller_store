<?php
// admin/api/product_categories.php
header('Content-Type: application/json');
require_once dirname(__DIR__) . '/config/db.php';
require_once dirname(__DIR__) . '/includes/session.php';

requireAdminLogin();

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

if ($method === 'GET') {
    $id_producto = $_GET['id_producto'] ?? null;
    if (!$id_producto) {
        echo json_encode(['success' => false, 'message' => 'ID de producto requerido']);
        exit;
    }
    
    $stmt = $conexion->prepare("SELECT c.* FROM categorias c 
                               INNER JOIN categorias_productos cp ON c.id = cp.id_categoria 
                               WHERE cp.id_producto = ? AND c.activo = 1");
    $stmt->bind_param("i", $id_producto);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $categories = [];
    while ($row = $result->fetch_assoc()) {
        $categories[] = $row;
    }
    echo json_encode(['success' => true, 'categories' => $categories]);
    exit;
}

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if ($action === 'sync') {
        $id_producto = $input['id_producto'] ?? null;
        $category_ids = $input['category_ids'] ?? []; // Array of IDs
        
        if (!$id_producto) {
            echo json_encode(['success' => false, 'message' => 'ID de producto requerido']);
            exit;
        }
        
        // Start transaction
        $conexion->begin_transaction();
        
        try {
            // Remove existing
            $stmt = $conexion->prepare("DELETE FROM categorias_productos WHERE id_producto = ?");
            $stmt->bind_param("i", $id_producto);
            $stmt->execute();
            
            // Add new
            if (!empty($category_ids)) {
                $stmt = $conexion->prepare("INSERT INTO categorias_productos (id_producto, id_categoria) VALUES (?, ?)");
                foreach ($category_ids as $id_cat) {
                    $stmt->bind_param("ii", $id_producto, $id_cat);
                    $stmt->execute();
                }
            }
            
            $conexion->commit();
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            $conexion->rollback();
            echo json_encode(['success' => false, 'message' => 'Error al sincronizar: ' . $e->getMessage()]);
        }
        exit;
    }
}

echo json_encode(['success' => false, 'message' => 'Acción no válida']);
?>
