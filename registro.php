<?php
/**
 * Página de Registro
 * Formulario de registro de nuevos usuarios
 */

require_once('core/db.php');
require_once('core/session.php');

// Si ya está logueado, redirigir a checkout
if (isLoggedIn()) {
    redirect('checkout.php');
}

$error = '';
$success = '';

// Procesar formulario de registro
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validar token CSRF
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        die('Error de validación CSRF. Por favor, intente de nuevo.');
    }
    
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';
    $nombre = trim($_POST['nombre'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    
    // Validaciones básicas
    if (empty($email) || empty($password) || empty($nombre) || empty($telefono)) {
        $error = 'Todos los campos son obligatorios.';
    } else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Por favor, ingrese un email válido.';
    } else if (strlen($password) < 6) {
        $error = 'La contraseña debe tener al menos 6 caracteres.';
    } else if ($password !== $password_confirm) {
        $error = 'Las contraseñas no coinciden.';
    } else {
        // Verificar si el email ya existe
        $stmt = $conexion_store->prepare("SELECT id FROM usuarios WHERE email = ? LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $error = 'Este email ya está registrado. Por favor, inicie sesión.';
        } else {
            // Hashear la contraseña
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            
            // Insertar nuevo usuario
            $stmt = $conexion_store->prepare("INSERT INTO usuarios (nombre, email, password, telefono, estado) VALUES (?, ?, ?, ?, 1)");
            $stmt->bind_param("ssss", $nombre, $email, $password_hash, $telefono);
            
            if ($stmt->execute()) {
                // Obtener el ID del usuario recién creado
                $user_id = $conexion_store->insert_id;
                
                // Iniciar sesión automáticamente
                $user = [
                    'id' => $user_id,
                    'nombre' => $nombre,
                    'email' => $email
                ];
                loginUser($user);
                
                // Redirigir a checkout
                redirect('checkout.php');
            } else {
                $error = 'Error al crear la cuenta. Por favor, intente nuevamente.';
            }
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
    <title>Crear Cuenta - iSeller Store</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
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
        .register-card {
            border: none;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-lg);
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            overflow: hidden;
        }
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(111, 175, 122, 0.25);
        }
        .btn-register {
            background-color: var(--primary-color);
            border: none;
            padding: 0.8rem;
            border-radius: 50px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .btn-register:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
            color: white;
        }
        .password-strength {
            height: 4px;
            border-radius: 2px;
            background: #e9ecef;
            margin-top: 5px;
            overflow: hidden;
        }
        .password-strength-bar {
            height: 100%;
            width: 0%;
            transition: all 0.3s ease;
        }
        .strength-weak { background: #dc3545; width: 33%; }
        .strength-medium { background: #ffc107; width: 66%; }
        .strength-strong { background: #198754; width: 100%; }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="text-center mb-4">
                    <a href="index.php" class="text-decoration-none">
                        <h2 class="fw-bold tracking-tight" style="color: var(--primary-dark)">
                            <i class="bi bi-shop-window me-2"></i>iSeller <span class="text-secondary">Store</span>
                        </h2>
                    </a>
                </div>

                <div class="card register-card p-4">
                    <div class="card-body">
                        <div class="text-center mb-4">
                            <h4 class="fw-bold text-dark">Crear una cuenta</h4>
                            <p class="text-muted small">Únete para disfrutar de la experiencia completa</p>
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

                        <form method="POST" action="" id="registerForm">
                            <?php echo csrf_field(); ?>
                            <div class="mb-3">
                                <label for="nombre" class="form-label small text-muted fw-bold">NOMBRE COMPLETO</label>
                                <input type="text" class="form-control bg-light" id="nombre" name="nombre" 
                                       placeholder="Tu nombre completo"
                                       value="<?php echo htmlspecialchars($_POST['nombre'] ?? ''); ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label for="email" class="form-label small text-muted fw-bold">EMAIL <span class="text-danger">*</span></label>
                                <input type="email" class="form-control bg-light" id="email" name="email" 
                                       placeholder="nombre@ejemplo.com"
                                       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                            </div>


                            <div class="mb-3">
                                <label for="telefono" class="form-label small text-muted fw-bold">TELÉFONO <span class="text-danger">*</span></label>
                                <input type="text" class="form-control bg-light" id="telefono" name="telefono" 
                                       placeholder="Tu número de teléfono" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="password" class="form-label small text-muted fw-bold">CONTRASEÑA <span class="text-danger">*</span></label>
                                <input type="password" class="form-control bg-light" id="password" name="password" 
                                       placeholder="Mínimo 6 caracteres" required>
                                <div class="password-strength">
                                    <div class="password-strength-bar" id="strengthBar"></div>
                                </div>
                                <small class="text-muted d-block mt-1" id="strengthText"></small>
                            </div>
                            
                            <div class="mb-4">
                                <label for="password_confirm" class="form-label small text-muted fw-bold">CONFIRMAR CONTRASEÑA <span class="text-danger">*</span></label>
                                <input type="password" class="form-control bg-light" id="password_confirm" name="password_confirm" required>
                            </div>
                            
                            <div class="d-grid mb-4">
                                <button type="submit" class="btn btn-register text-white">
                                    Registrarse
                                </button>
                            </div>

                            <div class="text-center border-top pt-3">
                                <p class="small text-muted mb-2">¿Ya tienes cuenta?</p>
                                <a href="login.php" class="fw-bold text-decoration-none" style="color: var(--primary-color)">
                                    Iniciar Sesión
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
    
    <script>
        // Password strength indicator
        const passwordInput = document.getElementById('password');
        const strengthBar = document.getElementById('strengthBar');
        const strengthText = document.getElementById('strengthText');
        
        passwordInput.addEventListener('input', function() {
            const password = this.value;
            let strength = 0;
            
            if (password.length >= 6) strength++;
            if (password.length >= 10) strength++;
            if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength++;
            if (/\d/.test(password)) strength++;
            if (/[^a-zA-Z\d]/.test(password)) strength++;
            
            strengthBar.className = 'password-strength-bar';
            
            if (password.length === 0) {
                strengthText.textContent = '';
            } else if (strength <= 2) {
                strengthBar.classList.add('strength-weak');
                strengthText.textContent = 'Débil';
                strengthText.style.color = '#dc3545';
            } else if (strength <= 3) {
                strengthBar.classList.add('strength-medium');
                strengthText.textContent = 'Media';
                strengthText.style.color = '#ffc107';
            } else {
                strengthBar.classList.add('strength-strong');
                strengthText.textContent = 'Fuerte';
                strengthText.style.color = '#198754';
            }
        });
        
        // Validación de confirmación de contraseña
        const form = document.getElementById('registerForm');
        const confirmPassword = document.getElementById('password_confirm');
        const telefonoInput = document.getElementById('telefono');

        telefonoInput.addEventListener('input', function() {
            this.value = this.value.replace(/[^0-9]/g, '');
            if (this.value.length > 11) {
                this.value = this.value.slice(0, 11);
            }
        });
        
        form.addEventListener('submit', function(e) {
            if (telefonoInput.value.length < 11) {
                e.preventDefault();
                telefonoInput.classList.add('is-invalid');
                if (!document.querySelector('.invalid-feedback')) {
                    const feedback = document.createElement('div');
                    feedback.className = 'invalid-feedback';
                    feedback.textContent = 'El teléfono debe tener al menos 11 dígitos';
                    telefonoInput.parentNode.appendChild(feedback);
                }
            }
            if (passwordInput.value !== confirmPassword.value) {
                e.preventDefault();
                confirmPassword.classList.add('is-invalid');
                if (!document.querySelector('.invalid-feedback')) {
                    const feedback = document.createElement('div');
                    feedback.className = 'invalid-feedback';
                    feedback.textContent = 'Las contraseñas no coinciden';
                    confirmPassword.parentNode.appendChild(feedback);
                }
            }
        });
        
        confirmPassword.addEventListener('input', function() {
            this.classList.remove('is-invalid');
        });
    </script>
</body>
</html>
