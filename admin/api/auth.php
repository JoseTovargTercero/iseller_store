<?php
// admin/api/auth.php
header('Content-Type: application/json');
require_once dirname(__DIR__) . '/config/db.php';
require_once dirname(__DIR__) . '/includes/session.php';

$input = json_decode(file_get_contents('php://input'), true);

// Validar token CSRF para peticiones JSON
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($input['csrf_token'] ?? '')) {
        echo json_encode(['success' => false, 'message' => 'Error de validación CSRF']);
        exit;
    }
}

$action = $input['action'] ?? $_GET['action'] ?? '';

if ($action === 'login') {
    $user = trim($input['username'] ?? ''); // Changed from usuario to username to match common usage or sticking to spanish
    if(empty($user)) $user = trim($input['usuario'] ?? '');
    
    $pass = trim($input['password'] ?? '');

    if (empty($user) || empty($pass)) {
        echo json_encode(['success' => false, 'message' => 'Usuario y contraseña requeridos']);
        exit;
    }

    $stmt = $conexion_store->prepare("SELECT id, usuario, password_hash FROM administradores WHERE usuario = ? LIMIT 1");
    $stmt->bind_param("s", $user);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($row = $res->fetch_assoc()) {
        if (password_verify($pass, $row['password_hash'])) {
            $_SESSION['admin_id'] = $row['id'];
            $_SESSION['admin_usuario'] = $row['usuario'];
            echo json_encode(['success' => true, 'redirect' => 'index.php']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Contraseña incorrecta']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Usuario no encontrado']);
    }
    exit;

} elseif ($action === 'logout') {
    session_unset();
    session_destroy();
    echo json_encode(['success' => true, 'redirect' => 'login.php']);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Acción no válida']);
?>
