<?php
/**
 * Sistema de Gestión de Sesiones
 * Maneja la autenticación y sesión de usuarios
 */

// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Verifica si el usuario está autenticado
 * @return bool True si está logueado, False si no
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Inicia sesión para un usuario
 * @param array $user Datos del usuario (id, nombre, email)
 * @return void
 */
function loginUser($user) {
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_nombre'] = $user['nombre'] ?? 'Usuario';
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['login_time'] = time();
    
    // Regenerar ID de sesión por seguridad
    session_regenerate_id(true);
}

/**
 * Cierra la sesión del usuario
 * @return void
 */
function logoutUser() {
    // Limpiar todas las variables de sesión
    $_SESSION = array();
    
    // Destruir la cookie de sesión si existe
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 3600, '/');
    }
    
    // Destruir la sesión
    session_destroy();
}

/**
 * Obtiene el ID del usuario actual
 * @return int|null ID del usuario o null si no está logueado
 */
function getUserId() {
    return isLoggedIn() ? $_SESSION['user_id'] : null;
}

/**
 * Obtiene el nombre del usuario actual
 * @return string|null Nombre del usuario o null si no está logueado
 */
function getUserName() {
    return isLoggedIn() ? $_SESSION['user_nombre'] : null;
}

/**
 * Obtiene el email del usuario actual
 * @return string|null Email del usuario o null si no está logueado
 */
function getUserEmail() {
    return isLoggedIn() ? $_SESSION['user_email'] : null;
}

/**
 * Redirige a una página específica
 * @param string $url URL de destino
 * @return void
 */
function redirect($url) {
    header("Location: $url");
    exit();
}

/**
 * Requiere que el usuario esté autenticado
 * Si no lo está, redirige al login
 * @param string $loginUrl URL del login (por defecto: login.php)
 * @return void
 */
function requireLogin($loginUrl = 'login.php') {
    if (!isLoggedIn()) {
        redirect($loginUrl);
    }
}



/**
 * Genera un token CSRF si no existe
 * @return string
 */
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Obtiene el token CSRF actual
 * @return string
 */
function getCSRFToken() {
    return generateCSRFToken();
}

/**
 * Valida un token CSRF
 * @param string $token
 * @return bool
 */
function validateCSRFToken($token) {
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Genera un campo input oculto con el token CSRF
 * @return string
 */
function csrf_field() {
    $token = getCSRFToken();
    return '<input type="hidden" name="csrf_token" value="' . $token . '">';
}

/**
 * Obtener el nivel del usuario
 * @return array|null [nivel, puntos] o null si no está logueado
 */
function getUserLevel() {
  // Consulta a la base de datos para obtener el nivel del usuario
  global $conexion_store;
  $userId = $_SESSION['user_id'] ?? null;
  if (!$userId) return null;

  $stmt = $conexion_store->prepare("SELECT nivel, puntos FROM usuarios WHERE id = ?");
  $stmt->bind_param("i", $userId);
  $stmt->execute();
  $result = $stmt->get_result();
  $row = $result->fetch_assoc();
  // si no encuentra el usuario eliminar la sesion
  if (!$row) {
    logoutUser();
    return null;
  }
  return [$row['nivel'], $row['puntos']];
}

