<?php
// Ensure $loyaltyData['foto'] is available for the navbar
if (isLoggedIn() && (!isset($loyaltyData) || !isset($loyaltyData['foto']))) {
    if (!isset($loyaltyData)) $loyaltyData = [];
    $uid_nav = getUserId();
    $stmt_nav = $conexion_store->prepare("SELECT foto FROM usuarios WHERE id = ?");
    $stmt_nav->bind_param("i", $uid_nav);
    $stmt_nav->execute();
    $loyaltyData['foto'] = $stmt_nav->get_result()->fetch_assoc()['foto'] ?? null;
    $stmt_nav->close();
}
?>
<nav class="navbar navbar-custom fixed-top" id="navbar">
        <div class="container-fluid d-flex align-items-center justify-content-between px-3 px-md-5">
            <!-- Left: Logo -->
            <a class="navbar-brand" href="index.php">
                <i class="bi bi-shop-window text-success"></i>
                <span style="color: var(--primary-color);">iSeller</span> <span style="color: var(--text-primary);">Store</span>
            </a>

      
            <!-- Center: Search (Hidden on small mobile) -->
            <?php if (basename($_SERVER['PHP_SELF']) === 'index.php'): ?>
            <div class="d-none d-md-flex flex-grow-1 justify-content-center mx-4">
                <div class="header-search-container">
                    <i class="bi bi-search text-muted"></i>
                    <input type="text" class="header-search-input" id="search" placeholder="Buscar productos..." autocomplete="off">
                </div>
                <!-- Search Results Dropdown -->
                <div id="search-results" class="search-results-container"></div>
            </div>
            <?php endif; ?>

            <!-- Right: Actions -->
            <div class="header-actions d-flex align-items-center gap-3">
             <?php if (isLoggedIn()): ?>
                <div class="reward-container position-relative hide" id="reward-dropdown">
                    <button class="btn-icon btn-reward" data-bs-toggle="modal" data-bs-target="#modalRecompensas" title="Tienes recompensas">
                        <i class="bi bi-gift-fill"></i>
                        <span class="badge bg-danger rounded-circle badge-reward" id="badge-reward">1</span>
                    </button>
                </div>
            <?php endif; ?>
                <?php if (basename($_SERVER['PHP_SELF']) === 'index.php'): ?>
                <button class="btn-icon" data-bs-toggle="modal" data-bs-target="#modalUbicacion" title="Nuestra Ubicación">
                    <i class="bi bi-geo-alt"></i>
                </button>
                <?php endif; ?>

             

                <?php if (isLoggedIn()): ?>
                <a href="perfil.php?tab=orders" class="btn-icon" title="Mis Compras">
                    <i class="bi bi-bag-check"></i>
                </a>
                <?php endif; ?>

                <div class="cart-container">
                    <button class="btn-icon btn-cart position-relative" id="cartDropdown" data-bs-toggle="modal" data-bs-target="#modalCarrito">
                        <i class="bi bi-cart"></i>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger shadow-sm border border-light" id="cart-count">
                            0
                        </span>
                    </button>
                </div>

                   <div class="dropdown">
                    <?php if (isLoggedIn()): ?>
                        <button class="btn-icon p-0 overflow-hidden" data-bs-toggle="dropdown" title="Mi Cuenta">
                            <?php if (!empty($loyaltyData['foto'])): ?>
                                <img src="assets/img/profiles/<?php echo $loyaltyData['foto']; ?>" style="width: 38px; height: 38px; object-fit: cover; border-radius: 50%;">
                            <?php else: ?>
                                <i class="bi bi-person"></i>
                            <?php endif; ?>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end custom-dropdown-menu">
                            <li><h6 class="dropdown-header"><?php echo htmlspecialchars(getUserName()); ?></h6></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="perfil.php"><i class="bi bi-person-circle me-2"></i> Mi Perfil</a></li>
                            <li><a class="dropdown-item" href="javascript:void(0)" onclick="shareReferralJS()"><i class="bi bi-share me-2"></i> Compartir Código de referido</a></li>
                            <li><a class="dropdown-item" href="javascript:void(0)" onclick="loadSavedCarts()"><i class="bi bi-folder-check me-2"></i> Cargar Carrito</a></li>
                            <li><a class="dropdown-item text-success fw-bold" href="iseller_store.apk" download><i class="bi bi-android2 me-2"></i> Descargar App</a></li>
                            <li><a class="dropdown-item" href="logout.php"><i class="bi bi-box-arrow-right me-2"></i> Cerrar Sesión</a></li>
                        </ul>
                    <?php else: ?>
                        <a href="login.php" class="btn-icon" title="Iniciar Sesión">
                            <i class="bi bi-person"></i>
                        </a>
                    <?php endif; ?>
                </div>

                <!-- Search Toggle Button (Mobile Only) -->
                <?php if (basename($_SERVER['PHP_SELF']) === 'index.php'): ?>
                <button class="btn-icon d-md-none" id="btn-search-toggle" title="Buscar">
                    <i class="bi bi-search"></i>
                </button>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Mobile Search Overlay (Animated) -->
        <?php if (basename($_SERVER['PHP_SELF']) === 'index.php'): ?>
        <div id="mobile-search-overlay" class="mobile-search-overlay d-md-none">
             <div class="header-search-container w-100">
                <i class="bi bi-search text-muted"></i>
                <input type="text" class="header-search-input" id="search-mobile" placeholder="Buscar productos..." autocomplete="off">
                <button class="btn border-0 p-0 ms-2" id="btn-search-close">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
             <!-- Mobile Search Results -->
             <div id="search-results-mobile" class="search-results-container"></div>
        </div>
        <?php endif; ?>

        <!-- Sticky Progress Bar (Hidden by default) -->
        <div id="navbar-progress-container" class="navbar-progress">
            <div class="navbar-progress-bar" style="width: 0%"></div>
        </div>
    </nav>