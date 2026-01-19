<?php
// admin/api/upload_product_image.php
header('Content-Type: application/json');
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

// Validate Image
$check = getimagesize($file["tmp_name"]);
if ($check === false) {
    echo json_encode(['success' => false, 'message' => 'El archivo no es una imagen.']);
    exit;
}

// Convert/Save as PNG
$targetFile = $targetDir . $productId . '.png';

try {
    // Create new image from file
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    switch($ext) {
        case 'jpg':
        case 'jpeg':
            $source = imagecreatefromjpeg($file['tmp_name']);
            break;
        case 'png':
            $source = imagecreatefrompng($file['tmp_name']);
            // Preserve alpha for PNG
            imagealphablending($source, true);
            imagesavealpha($source, true);
            break;
        case 'webp':
            $source = imagecreatefromwebp($file['tmp_name']);
            break;
        case 'gif':
            $source = imagecreatefromgif($file['tmp_name']);
            break;
        default:
             // Try to let gd figure it out or fail
             echo json_encode(['success' => false, 'message' => 'Formato no soportado (use jpg, png, webp)']);
             exit;
    }

    if (!$source) {
         echo json_encode(['success' => false, 'message' => 'Error procesando la imagen']);
         exit;
    }

    // Save as PNG
    if (imagepng($source, $targetFile)) {
        imagedestroy($source);
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error guardando archivo']);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
