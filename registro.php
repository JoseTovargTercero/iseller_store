<?php
/**
 * Página de Registro
 * Formulario de registro de nuevos usuarios
 */

require_once('core/db.php');
require_once('core/session.php');

// Si ya está logueado, redirigir
if (isLoggedIn()) {
    // Nota: Aquí no tenemos el valor de cart_exists del POST, por lo que redirigimos a checkout por defecto o a index
    redirect('checkout.php');
}

$error = '';
$success = '';

// Procesar formulario de registro
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    function generateReferralCode($userId) {
        return strtoupper(substr(md5($userId . uniqid()), 0, 8));
    }

    // Validar token CSRF
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        die('Error de validación CSRF. Por favor, intente de nuevo.');
    }
    
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';
    $nombre = trim($_POST['nombre'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $cart_exists = $_POST['cart_exists'] ?? '0';
    
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

            $conexion_store->begin_transaction();
            try {
                // Hashear la contraseña
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                
                // Insertar nuevo usuario
                $stmt = $conexion_store->prepare("INSERT INTO usuarios (nombre, email, password, telefono, estado) VALUES (?, ?, ?, ?, 1)");
                $stmt->bind_param("ssss", $nombre, $email, $password_hash, $telefono);
                
                if ($stmt->execute()) {
                
                    // Obtener el ID del usuario recién creado
                    $user_id = $conexion_store->insert_id;
                    // Generar código de referido
                    $referral_code = generateReferralCode($user_id);
                    // actualizar usuarios.referral_code 
                    $stmt = $conexion_store->prepare("UPDATE usuarios SET referral_code = ? WHERE id = ?");
                    $stmt->bind_param("ss", $referral_code, $user_id);
                    if(!$stmt->execute()){
                        throw new Exception("Error al actualizar el código de referido");
                    }

                    // Iniciar sesión automáticamente
                    $user = [
                        'id' => $user_id,
                        'nombre' => $nombre,
                        'email' => $email
                    ];
                    loginUser($user);

                    // Verificar si fue referido
                    $referralCode = $_COOKIE['referral_code'] ?? null;

                    if ($referralCode) {
                        $stmt = $conexion_store->prepare("SELECT id FROM usuarios WHERE referral_code = ? LIMIT 1");
                        $stmt->bind_param("s", $referralCode);
                        if(!$stmt->execute()){
                            throw new Exception("Error al buscar el código de referido");
                        }
                        $result = $stmt->get_result();
                        if ($result->num_rows > 0) {
                            echo 'entro';
                            $referrer = $result->fetch_assoc();
                            $referrer_id = $referrer['id'];
                            // Insertar la referencia
                            $stmt = $conexion_store->prepare("INSERT INTO referrals (referrer_user_id, referred_user_id, referral_code, status) VALUES (?, ?, ?, 'pending')");
                            $stmt->bind_param("iis", $referrer_id, $user_id, $referralCode);
                            if(!$stmt->execute()){
                                throw new Exception("Error al insertar la referencia " . $stmt->error);
                            }
                        }

                        // eliminar cookie
                        setcookie('referral_code', '', time() - 3600, '/');

                    }
                    $conexion_store->commit();
                    $redirect_page = ($cart_exists === '1') ? 'checkout.php' : 'index.php';
                    redirect($redirect_page);
                } else {
                    $error = 'Error al crear la cuenta. Por favor, intente nuevamente.';
                    $conexion_store->rollback();
                }

            } catch (Exception $e) {
                $error = $e->getMessage();
                $conexion_store->rollback();
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
    
    <!-- Dexie.js for IndexedDB -->
    <script src="https://cdn.jsdelivr.net/npm/dexie@3.2.4/dist/dexie.min.js"></script>
    
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
<body class="bg-light">
    <?php include 'includes/navbar.php'; ?>
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
                            <input type="hidden" name="cart_exists" id="cart_exists" value="0">
                            <div class="mb-3">
                                <label for="nombre" class="form-label small text-muted fw-bold">NOMBRE Y APELLIDO</label>
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

        // Verificación de carrito en IndexedDB
        const db = new Dexie("POS_DB");
        db.version(2).stores({
            carritoActivo: 'id',
            carritosVenta: 'id',
            carritosReservados: 'id',
            cart_meta: 'id'
        });

        async function checkCartStatus() {
            try {
                const count = await db.carritoActivo.count();
                document.getElementById('cart_exists').value = count > 0 ? '1' : '0';
                console.log('Cart status checked:', count > 0);
            } catch (e) {
                console.error("Error al verificar el carrito:", e);
            }
        }

        // Ejecutar al cargar y antes de enviar el formulario
        document.addEventListener('DOMContentLoaded', checkCartStatus);
        form.addEventListener('submit', checkCartStatus);
    </script>
</body>
</html>
