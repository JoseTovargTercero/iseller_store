<?php
// admin/includes/nav.php
require_once 'session.php';
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top">
    <div class="container-fluid px-4">
        <a class="navbar-brand fw-bold" href="index.php">
            <i class="bi bi-speedometer2 text-primary"></i> iSeller Admin
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active fw-bold text-primary' : ''; ?>" href="index.php">
                        <i class="bi bi-cart-check"></i> Pedidos
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'productos.php' ? 'active fw-bold text-primary' : ''; ?>" href="productos.php">
                        <i class="bi bi-box-seam"></i> Productos
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'rendimiento.php' ? 'active fw-bold text-primary' : ''; ?>" href="rendimiento.php">
                        <i class="bi bi-graph-up-arrow"></i> Rendimiento
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'chat_panel.php' ? 'active fw-bold text-primary' : ''; ?>" href="chat_panel.php">
                        <i class="bi bi-chat-dots-fill"></i> Chat
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'clientes.php' ? 'active fw-bold text-primary' : ''; ?>" href="clientes.php">
                        <i class="bi bi-people"></i> Clientes
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'recompensas.php' ? 'active fw-bold text-primary' : ''; ?>" href="recompensas.php">
                        <i class="bi bi-gift"></i> Recompensas
                    </a>
                </li>
            </ul>
            <ul class="navbar-nav ms-auto align-items-center">
                <li class="nav-item me-3">
                    <span class="text-white small">Hola, <strong><?php echo htmlspecialchars(getAdminUsername()); ?></strong></span>
                </li>
                <li class="nav-item">
                    <button id="btnLogout" class="btn btn-outline-light btn-sm rounded-pill px-3">
                        <i class="bi bi-box-arrow-right"></i> Salir
                    </button>
                </li>
            </ul>
        </div>
    </div>
</nav>
