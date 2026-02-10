<?php
/**
 * Sistema de Métricas de Visitantes
 */

/**
 * Registra una visita en la base de datos
 */
function registrarVisita($conexion) {
    // Evitar registrar visitas de bots comunes para no inflar las métricas
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    if (preg_match('/bot|crawl|slurp|spider|mediapartners/i', $userAgent)) {
        return;
    }

    $usuarioId = $_SESSION['user_id'] ?? null;
    $sessionId = session_id();
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '';
    $userAgent = substr($userAgent, 0, 500); // Limitar longitud
    $pageUrl = $_SERVER['REQUEST_URI'] ?? '';
    $referrer = $_SERVER['HTTP_REFERER'] ?? null;

    // Solo registrar si no es una petición AJAX interna (opcional, pero recomendado)
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        return;
    }

    $stmt = $conexion->prepare("INSERT INTO visitas (usuario_id, session_id, ip_address, user_agent, page_url, referrer) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isssss", $usuarioId, $sessionId, $ipAddress, $userAgent, $pageUrl, $referrer);
    $stmt->execute();
    $stmt->close();
}
