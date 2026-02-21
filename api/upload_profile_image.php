<?php
// api/upload_profile_image.php
header('Content-Type: application/json');
require_once '../core/db.php';
require_once '../core/session.php';

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$user_id = getUserId();
if (!isset($_FILES['image'])) {
    echo json_encode(['success' => false, 'message' => 'Faltan datos']);
    exit;
}

$file = $_FILES['image'];
$targetDir = dirname(__DIR__) . '/assets/img/profiles/';

// Create directory if not exists
if (!file_exists($targetDir)) {
    mkdir($targetDir, 0777, true);
}

// Validate Image
$check = getimagesize($file["tmp_name"]);
if ($check === false) {
    echo json_encode(['success' => false, 'message' => 'El archivo no es una imagen.']);
    exit;
}

// Allowed extensions
$allowedExts = ['jpg', 'jpeg', 'png', 'webp'];
$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

if (!in_array($ext, $allowedExts)) {
    echo json_encode(['success' => false, 'message' => 'Formato no soportado (use jpg, png, webp)']);
    exit;
}

// Save filename as user_id.png for consistency or keep original ext
$filename = $user_id . '_' . time() . '.' . $ext;
$targetFile = $targetDir . $filename;

try {
    if (move_uploaded_file($file['tmp_name'], $targetFile)) {
        // Update database
        $stmt = $conexion_store->prepare("UPDATE usuarios SET foto = ? WHERE id = ?");
        $stmt->bind_param("si", $filename, $user_id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'foto' => $filename]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al actualizar la base de datos']);
        }
        $stmt->close();
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al guardar el archivo']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
