<?php
// admin/includes/session.php

if (session_status() == PHP_SESSION_NONE) {
    // Set a custom session name for admin to separate from frontend
    session_name('ISellerAdminSession');
    session_start();
}

function isAdminLoggedIn() {
    return isset($_SESSION['admin_id']);
}

function requireAdminLogin() {
    if (!isAdminLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

function getAdminId() {
    return $_SESSION['admin_id'] ?? null;
}


function getAdminUsername() {
    return $_SESSION['admin_usuario'] ?? null;
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
?>
