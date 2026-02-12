<?php
require_once('core/db.php');
require_once('core/session.php');

header('Content-Type: application/json');

$response = [
    "value" => false,
    "message" => "Error desconocido"
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    function generateReferralCode($userId) {
        return strtoupper(substr(md5($userId . uniqid()), 0, 8));
    }

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';
    $nombre = trim($_POST['nombre'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');

    // ===== VALIDACIONES =====

    if (empty($email) || empty($password) || empty($nombre) || empty($telefono)) {
        $response['message'] = "Todos los campos son obligatorios.";
        echo json_encode($response);
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response['message'] = "Email inválido.";
        echo json_encode($response);
        exit;
    }

    if (strlen($password) < 6) {
        $response['message'] = "La contraseña debe tener al menos 6 caracteres.";
        echo json_encode($response);
        exit;
    }

    if ($password !== $password_confirm) {
        $response['message'] = "Las contraseñas no coinciden.";
        echo json_encode($response);
        exit;
    }

    // ===== VERIFICAR EMAIL =====

    $stmt = $conexion_store->prepare("SELECT id FROM usuarios WHERE email = ? LIMIT 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $response['message'] = "Este email ya está registrado.";
        echo json_encode($response);
        exit;
    }
    $stmt->close();

    // ===== CREAR USUARIO =====

    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $conexion_store->prepare("
        INSERT INTO usuarios (nombre, email, password, telefono, estado) 
        VALUES (?, ?, ?, ?, 1)
    ");
    $stmt->bind_param("ssss", $nombre, $email, $password_hash, $telefono);

    if (!$stmt->execute()) {
        $response['message'] = "Error al crear la cuenta.";
        echo json_encode($response);
        exit;
    }

    $user_id = $conexion_store->insert_id;
    $stmt->close();

    // ===== GENERAR CÓDIGO DE REFERIDO =====

    $referral_code = generateReferralCode($user_id);

    $stmt = $conexion_store->prepare("UPDATE usuarios SET referral_code = ? WHERE id = ?");
    $stmt->bind_param("si", $referral_code, $user_id);
    $stmt->execute();
    $stmt->close();

    // ===== RESPUESTA EXITOSA =====

    $response = [
        "value" => true,
        "message" => "Cuenta creada correctamente",
        "data" => [
            "id" => $user_id,
            "nombre" => $nombre,
            "email" => $email,
            "telefono" => $telefono,
            "referral_code" => $referral_code
        ]
    ];

    echo json_encode($response);
    exit;
}

// ===== MÉTODO NO PERMITIDO =====

$response['message'] = "Método no permitido";
echo json_encode($response);
