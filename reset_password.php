<?php
/**
 * Página de Restablecimiento de Contraseña
 */

require_once('core/db.php');
require_once('core/session.php');

$token = $_GET['token'] ?? '';
$error = '';
$success = '';
$user_id = null;

if (empty($token)) {
    die('Token inválido.');
}

// Validar token y expiración
$stmt = $conexion_store->prepare("SELECT id FROM usuarios WHERE reset_token = ? AND reset_expires > NOW() LIMIT 1");
$stmt->bind_param("s", $token);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die('El enlace de recuperación es inválido o ha expirado.');
}

$user = $result->fetch_assoc();
$user_id = $user['id'];
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        die('Error CSRF.');
    }

    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (strlen($password) < 6) {
        $error = 'La contraseña debe tener al menos 6 caracteres.';
    } elseif ($password !== $confirm) {
        $error = 'Las contraseñas no coinciden.';
    } else {
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        
        // Actualizar contraseña y limpiar token
        $update = $conexion_store->prepare("UPDATE usuarios SET password = ?, reset_token = NULL, reset_expires = NULL WHERE id = ?");
        $update->bind_param("si", $hashed, $user_id);
        
        if ($update->execute()) {
            $success = '¡Contraseña actualizada correctamente! Ahora puedes iniciar sesión.';
        } else {
            $error = 'Error al actualizar la contraseña.';
        }
        $update->close();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restablecer Contraseña - iSeller Store</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/global-styles.css">
    <style>
        body { background-color: var(--bg-secondary); display: flex; align-items: center; justify-content: center; min-height: 100vh; }
        .login-card { border: none; border-radius: var(--radius-lg); box-shadow: var(--shadow-lg); background: white; }
    </style>
</head>
<body class="bg-light">
    <?php include 'includes/navbar.php'; ?>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-5 col-lg-4">
                <div class="card login-card p-4">
                    <div class="card-body">
                        <div class="text-center mb-4">
                            <h4 class="fw-bold text-dark">Nueva Contraseña</h4>
                            <p class="text-muted small">Crea una nueva contraseña segura para tu cuenta.</p>
                        </div>

                        <?php if ($error): ?>
                            <div class="alert alert-danger small py-2"><?php echo $error; ?></div>
                        <?php endif; ?>

                        <?php if ($success): ?>
                            <div class="alert alert-success small py-3">
                                <i class="bi bi-check-circle-fill me-2"></i><?php echo $success; ?>
                                <div class="mt-3">
                                    <a href="login.php" class="btn btn-primary btn-sm rounded-pill px-4 text-white" style="background-color: var(--primary-color); border: none;">Ir al Login</a>
                                </div>
                            </div>
                        <?php else: ?>
                            <form method="POST">
                                <?php echo csrf_field(); ?>
                                <div class="mb-3">
                                    <label class="form-label small text-muted fw-bold">NUEVA CONTRASEÑA</label>
                                    <input type="password" class="form-control bg-light" name="password" required minlength="6" placeholder="••••••••">
                                </div>

                                <div class="mb-4">
                                    <label class="form-label small text-muted fw-bold">CONFIRMAR CONTRASEÑA</label>
                                    <input type="password" class="form-control bg-light" name="confirm_password" required minlength="6" placeholder="••••••••">
                                </div>

                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary rounded-pill py-2 fw-bold text-white" style="background-color: var(--primary-color); border: none;">Restablecer contraseña</button>
                                </div>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
