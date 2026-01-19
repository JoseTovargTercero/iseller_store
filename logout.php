<?php
/**
 * Logout - Cerrar Sesión
 */

require_once('core/session.php');

// Cerrar sesión
logoutUser();

// Redirigir al index
redirect('index.php');
