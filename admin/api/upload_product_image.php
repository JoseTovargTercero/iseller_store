<?php
// admin/api/upload_product_image.php
header('Content-Type: application/json');

// Ensure no warnings/notices break JSON
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Custom error handler to return JSON
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    if (!(error_reporting() & $errno)) return;
    echo json_encode([
        'success' => false,
        'message' => "PHP Error ($errno): $errstr in $errfile on line $errline"
    ]);
    exit;
});

require_once dirname(__DIR__) . '/includes/session.php';

requireAdminLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Check for post_max_size exceeded
if (empty($_POST) && empty($_FILES) && $_SERVER['CONTENT_LENGTH'] > 0) {
    echo json_encode(['success' => false, 'message' => 'Los datos enviados exceden el límite permitido por el servidor (post_max_size).']);
    exit;
}

$productId = $_POST['id'] ?? $_GET['id'] ?? null;
if (!$productId || !isset($_FILES['image'])) {
    echo json_encode(['success' => false, 'message' => 'Faltan datos (ID: ' . ($productId ?: 'null') . ', Imagen: ' . (isset($_FILES['image']) ? 'Si' : 'No') . ')']);
    exit;
}

$file = $_FILES['image'];

// Check upload errors
if ($file['error'] !== UPLOAD_ERR_OK) {
    $errors = [
        UPLOAD_ERR_INI_SIZE   => 'El archivo excede upload_max_filesize.',
        UPLOAD_ERR_FORM_SIZE  => 'El archivo excede MAX_FILE_SIZE.',
        UPLOAD_ERR_PARTIAL    => 'El archivo se subió parcialmente.',
        UPLOAD_ERR_NO_FILE    => 'No se subió ningún archivo.',
        UPLOAD_ERR_NO_TMP_DIR => 'Falta la carpeta temporal.',
        UPLOAD_ERR_CANT_WRITE => 'Error al escribir el archivo en el disco.',
        UPLOAD_ERR_EXTENSION  => 'Una extensión de PHP detuvo la subida.'
    ];
    $msg = $errors[$file['error']] ?? 'Error desconocido en la subida.';
    echo json_encode(['success' => false, 'message' => $msg]);
    exit;
}

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
