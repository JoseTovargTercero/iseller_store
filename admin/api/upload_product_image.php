<?php
// admin/api/upload_product_image.php
header('Content-Type: application/json');

// Ensure no warnings/notices break JSON
error_reporting(0);
ini_set('display_errors', 0);

require_once dirname(__DIR__) . '/includes/session.php';

requireAdminLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$productId = $_POST['id'] ?? null;
if (!$productId || !isset($_FILES['image'])) {
    echo json_encode(['success' => false, 'message' => 'Faltan datos']);
    exit;
}

$file = $_FILES['image'];
$targetDir = dirname(dirname(__DIR__)) . '/assets/img/stock/';

// Check if directory exists and is writable
if (!file_exists($targetDir)) {
    if (!@mkdir($targetDir, 0777, true)) {
        echo json_encode(['success' => false, 'message' => 'El directorio de destino no existe y no se pudo crear.']);
        exit;
    }
}

if (!is_writable($targetDir)) {
    echo json_encode(['success' => false, 'message' => 'El servidor no tiene permisos de escritura en: ' . basename($targetDir)]);
    exit;
}

// Validate Image
$check = @getimagesize($file["tmp_name"]);
if ($check === false) {
    echo json_encode(['success' => false, 'message' => 'El archivo no es una imagen válida o es demasiado grande.']);
    exit;
}

// Convert/Save as PNG
$targetFile = $targetDir . $productId . '.png';

try {
    // Create new image from file
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $source = null;
    
    switch($ext) {
        case 'jpg':
        case 'jpeg':
            $source = @imagecreatefromjpeg($file['tmp_name']);
            break;
        case 'png':
            $source = @imagecreatefrompng($file['tmp_name']);
            if ($source) {
                imagealphablending($source, true);
                imagesavealpha($source, true);
            }
            break;
        case 'webp':
            $source = @imagecreatefromwebp($file['tmp_name']);
            break;
        case 'gif':
            $source = @imagecreatefromgif($file['tmp_name']);
            break;
        default:
             echo json_encode(['success' => false, 'message' => 'Formato no soportado (use jpg, png, webp)']);
             exit;
    }

    if (!$source) {
         echo json_encode(['success' => false, 'message' => 'Error procesando la imagen. Asegúrese de que la librería GD esté habilitada.']);
         exit;
    }

    // Save as PNG
    if (@imagepng($source, $targetFile)) {
        imagedestroy($source);
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error guardando archivo. Verifique espacio en disco o permisos.']);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Excepción: ' . $e->getMessage()]);
}
?>
