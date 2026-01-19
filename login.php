<?php
/**
 * Página de Login
 * Formulario de inicio de sesión y procesamiento
 */

require_once('core/db.php');
require_once('core/session.php');

// Si ya está logueado, redirigir a checkout
if (isLoggedIn()) {
    redirect('index.php');
}

$error = '';
$success = '';

// Procesar formulario de login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validar token CSRF
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        die('Error de validación CSRF. Por favor, intente de nuevo.');
    }
    
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Validaciones básicas
    if (empty($email) || empty($password)) {
        $error = 'Por favor, complete todos los campos.';
    } else {
        // Consultar usuario en la base de datos
        $stmt = $conexion_store->prepare("SELECT id, nombre, email, password, estado FROM usuarios WHERE email = ? LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            
            // Verificar si el usuario está activo
            if ($user['estado'] != 1) {
                $error = 'Esta cuenta está desactivada. Contacte al administrador.';
            } 
            // Verificar contraseña
            else if (password_verify($password, $user['password'])) {
                // Login exitoso
                loginUser($user);
                redirect('index.php');
            } else {
                $error = 'Email o contraseña incorrectos.';
            }
        } else {
            $error = 'Email o contraseña incorrectos.';
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
    <title>Iniciar Sesión - iSeller Store</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <!-- Global Styles -->
    <link rel="stylesheet" href="assets/css/global-styles.css">
    
    <style>
        body {
            background-color: var(--bg-secondary);
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }
        .login-card {
            border: none;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-lg);
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            overflow: hidden;
        }
        .login-header {
            background: var(--primary-color);
            color: white;
            padding: 2rem;
            text-align: center;
        }
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(111, 175, 122, 0.25);
        }
        .btn-login {
            background-color: var(--primary-color);
            border: none;
            padding: 0.8rem;
            border-radius: 50px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .btn-login:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
            color: white;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-5 col-lg-4">
                <div class="text-center mb-4">
                    <a href="index.php" class="text-decoration-none">
                        <h2 class="fw-bold tracking-tight" style="color: var(--primary-dark)">
                            <i class="bi bi-shop-window me-2"></i>iSeller <span class="text-secondary">Store</span>
                        </h2>
                    </a>
                </div>

                <div class="card login-card p-4">
                    <div class="card-body">
                        <div class="text-center mb-4">
                            <h4 class="fw-bold text-dark">Bienvenido de nuevo</h4>
                            <p class="text-muted small">Ingresa a tu cuenta para continuar</p>
                        </div>

                        <!-- Mensajes de error/éxito -->
                        <?php if (!empty($error)): ?>
                            <div class="alert alert-danger d-flex align-items-center small py-2" role="alert">
                                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                <div><?php echo htmlspecialchars($error); ?></div>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($success)): ?>
                            <div class="alert alert-success d-flex align-items-center small py-2" role="alert">
                                <i class="bi bi-check-circle-fill me-2"></i>
                                <div><?php echo htmlspecialchars($success); ?></div>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="">
                            <?php echo csrf_field(); ?>
                            <div class="mb-3">
                                <label class="form-label small text-muted fw-bold">EMAIL</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0"><i class="bi bi-envelope text-muted"></i></span>
                                    <input type="email" class="form-control border-start-0 ps-0 bg-light" name="email" 
                                           value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" 
                                           placeholder="nombre@ejemplo.com" required autofocus>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="form-label small text-muted fw-bold">CONTRASEÑA</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0"><i class="bi bi-lock text-muted"></i></span>
                                    <input type="password" class="form-control border-start-0 ps-0 bg-light" name="password" 
                                           placeholder="••••••••" required>
                                </div>
                                <div class="text-end mt-2">
                                    <a href="forgot_password.php" class="small text-decoration-none" style="color: var(--primary-color)">¿Olvidaste tu contraseña?</a>
                                </div>
                            </div>

                            <div class="d-grid mb-4">
                                <button type="submit" class="btn btn-login text-white">
                                    Iniciar Sesión
                                </button>
                            </div>

                            <div class="text-center border-top pt-3">
                                <p class="small text-muted mb-2">¿No tienes una cuenta?</p>
                                <a href="registro.php" class="fw-bold text-decoration-none" style="color: var(--primary-color)">
                                    Crear cuenta nueva
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
                
                <div class="text-center mt-4">
                    <a href="index.php" class="small text-muted text-decoration-none hover-link">
                        <i class="bi bi-arrow-left"></i> Volver a la tienda
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
