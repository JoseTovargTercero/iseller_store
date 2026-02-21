<?php
/**
 * Página de Recuperación de Contraseña (Solicitud)
 */

require_once('core/db.php');
require_once('core/session.php');

if (isLoggedIn()) {
    redirect('index.php');
}

$error = '';
$success = '';
$reset_link = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        die('Error de validación CSRF.');
    }

    $email = trim($_POST['email'] ?? '');

    if (empty($email)) {
        $error = 'Por favor, ingrese su correo electrónico.';
    } else {
        // Verificar si el correo existe
        $stmt = $conexion_store->prepare("SELECT id FROM usuarios WHERE email = ? LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

            // Guardar token en DB
            $update = $conexion_store->prepare("UPDATE usuarios SET reset_token = ?, reset_expires = ? WHERE id = ?");
            $update->bind_param("ssi", $token, $expires, $user['id']);
            
            if ($update->execute()) {
                $success = 'Se ha generado un enlace de recuperación.';
                // En un entorno real, aquí se enviaría el correo.
                // Simulamos el enlace para el usuario:
                $reset_link = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]" . dirname($_SERVER['PHP_SELF']) . "/reset_password.php?token=" . $token;
            } else {
                $error = 'Hubo un error al procesar la solicitud.';
            }
            $update->close();
        } else {
            // Por seguridad, no decimos si el email existe o no, pero en este caso iSeller suele ser amigable
            $error = 'No encontramos ninguna cuenta con ese correo electrónico.';
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Contraseña - iSeller Store</title>
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
                            <h4 class="fw-bold text-dark">Recuperar Contraseña</h4>
                            <p class="text-muted small">Ingresa tu email y te enviaremos un enlace para restablecer tu cuenta.</p>
                        </div>

                        <?php if ($error): ?>
                            <div class="alert alert-danger small py-2"><i class="bi bi-exclamation-circle me-2"></i><?php echo $error; ?></div>
                        <?php endif; ?>

                        <?php if ($success): ?>
                            <div class="alert alert-success small py-3">
                                <i class="bi bi-check-circle-fill me-2"></i><?php echo $success; ?>
                                <?php if ($reset_link): ?>
                                    <hr>
                                    <p class="mb-1 fw-bold">Enlace de prueba:</p>
                                    <a href="<?php echo $reset_link; ?>" class="alert-link break-all" style="word-break: break-all;"><?php echo $reset_link; ?></a>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST">
                            <?php echo csrf_field(); ?>
                            <div class="mb-4">
                                <label class="form-label small text-muted fw-bold">EMAIL</label>
                                <input type="email" class="form-control bg-light" name="email" required placeholder="nombre@ejemplo.com">
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary rounded-pill py-2 fw-bold text-white" style="background-color: var(--primary-color); border: none;">Enviar enlace</button>
                                <a href="login.php" class="btn btn-link btn-sm text-decoration-none text-muted">Volver al inicio de sesión</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
