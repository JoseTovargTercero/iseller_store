<?php
require_once('core/check_maintenance.php');
require_once('core/db.php');
require_once('core/session.php');
require_once('core/_tasas_cambio.php');

$_SESSION['sucursal'] = 9;
$_SESSION['bss_id'] = 3;
$_SESSION['nivel'] = 1;

$nivelUsuario = $_SESSION['nivel'];
$bss_id = $_SESSION['bss_id'];
$sucursal = $_SESSION['sucursal'];


require("core/_calculadrora_precios.php");
$calculadora = new CalculadoraPrecios($pesoDolar, $peso_bolivar, $dolarBolivar, $bolivar_peso, $bcv, $data_monedas);


include 'core/la-carta.php';
$cart = new Cart;



// La carga de productos se ha movido a la API (api/productos.php) para mejorar el rendimiento.
// Se ha eliminado la consulta masiva inicial.

// --- LOGICA PUNTOS DE FIDELIDAD ---
$loyaltyData = [
    'puntos' => 0,
    'nivel' => 1,
    'progreso' => 0,
    'porcentaje' => 0,
    'falta' => 10
];

if (isLoggedIn()) {
    $uid = getUserId();
    // Usamos conexion_store que es donde está la tabla usuarios según checkout.php
    $stmtLoyalty = $conexion_store->prepare("SELECT puntos, nivel FROM usuarios WHERE id = ?");
    $stmtLoyalty->bind_param("i", $uid);
    $stmtLoyalty->execute();
    $resLoyalty = $stmtLoyalty->get_result();
    
    if ($rowLoyalty = $resLoyalty->fetch_assoc()) {
        $puntos = floatval($rowLoyalty['puntos']);
        $nivel = intval($rowLoyalty['nivel']);
        
        // Calcular progreso (Cada 10 puntos es un nivel)
        $progreso = fmod($puntos, 10); // Puntos en el nivel actual
        $porcentaje = ($progreso / 10) * 100;
        $falta = 10 - $progreso;
        
        $loyaltyData = [
            'puntos' => $puntos,
            'nivel' => $nivel,
            'progreso' => $progreso,
            'porcentaje' => $porcentaje,
            'falta' => $falta
        ];
    }
    $stmtLoyalty->close();
}
// -----------------------------------

?>

<!DOCTYPE html>
<html lang='es'>
<head>
 <meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>iSeller Store | Ecommerce para comprar en línea fácil y seguro</title>

<!-- SEO Básico -->
<meta name="description" content="iSeller Store es un ecommerce donde puedes comprar productos en línea de forma segura, rápida y confiable. Descubre ofertas y paga fácilmente.">
<meta name="keywords" content="ecommerce, tienda online, comprar en línea, iseller store, compras online, marketplace">
<meta name="author" content="iSeller Store">
<meta name="robots" content="index, follow">

<!-- Open Graph -->
<meta property="og:title" content="iSeller Store | Compra en línea fácil y seguro">
<meta property="og:description" content="Compra fácil y seguro en iSeller Store. Regístrate y obtén $5 de regalo.">
<meta property="og:type" content="website">
<meta property="og:url" content="https://iseller-tiendas.com/">
<meta property="og:image" content="https://iseller-tiendas.com/store/assets/img/og-image.jpg">

<!-- Twitter Card -->
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="iSeller Store | Compra en línea fácil y seguro">
<meta name="twitter:description" content="Ecommerce confiable para comprar productos en línea de forma rápida y segura.">
<meta name="twitter:image" content="https://iseller-tiendas.com/store/assets/img/og-image.jpg">

<!-- Favicon -->
<link rel="icon" type="image/png" sizes="32x32" href="assets/img/icon.PNG">
<link rel="icon" type="image/png" sizes="16x16" href="assets/img/icon.PNG">
<link rel="apple-touch-icon" sizes="180x180" href="assets/img/icon.PNG">

<!-- Bootstrap CSS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

<!-- Bootstrap Icons -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

<!-- Custom Styles -->
<link rel="stylesheet" href="assets/css/global-styles.css">

<!-- Chat System CSS -->
<link rel="stylesheet" href="assets/css/chat.css">

<!-- Leaflet CSS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="">

    <style>
        /* Rewards Bar Styles */
        .rewards-bar-container {
            background: white;
            border-bottom: 1px solid #e5e7eb;
            position: relative;
            top: 70px; /* Adjust based on navbar height */
            z-index: 99;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
        }
        
        .rewards-content {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1.5rem;
            padding: 0.75rem 0;
        }

        .level-badge {
            background: linear-gradient(135deg, var(--primary-color), #047857);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 99px;
            font-weight: 700;
            white-space: nowrap;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .progress-wrapper {
            flex-grow: 1;
            position: relative;
        }

        .progress-text {
            display: flex;
            justify-content: space-between;
            font-size: 0.85rem;
            margin-bottom: 0.25rem;
            color: #4b5563;
            font-weight: 500;
        }

        .custom-progress {
            height: 10px;
            background-color: #f3f4f6;
            border-radius: 99px;
            overflow: hidden;
            box-shadow: inset 0 1px 2px rgba(0,0,0,0.05);
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--primary-color), #34d399);
            width: 0%; /* Animated via JS */
            border-radius: 99px;
            transition: width 1.5s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }
        
        /* Shimmer effect */
       /* .progress-fill::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            bottom: 0;
            right: 0;
            background-image: linear-gradient(
                -45deg, 
                rgba(255, 255, 255, 0.2) 25%, 
                transparent 25%, 
                transparent 50%, 
                rgba(255, 255, 255, 0.2) 50%, 
                rgba(255, 255, 255, 0.2) 75%, 
                transparent 75%, 
                transparent
            );
            background-size: 20px 20px;
            animation: moveStripes 1s linear infinite;
        }

        @keyframes moveStripes {
            0% { background-position: 0 0; }
            100% { background-position: 20px 20px; }
        }

        .reward-info {
            font-size: 0.8rem;
            color: #6b7280;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .pulse-badge {
            animation: pulse-green 2s infinite;
        }

        @keyframes pulse-green {
            0% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7); }
            70% { box-shadow: 0 0 0 10px rgba(16, 185, 129, 0); }
            100% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
        }
*/
        /* Mobile specific adjustments */
        @media (max-width: 768px) {
            .rewards-bar-container {
                top: 0px; /* Navbar mobile height approx */
                padding: 0 15px;
            }
            .rewards-content {
                flex-direction: column;
                gap: 0.5rem;
                align-items: stretch;
            }
            .level-info-row {
                display: flex;
                justify-content: space-between;
                align-items: center;
            }
            .level-badge {
                padding: 0.25rem 0.75rem;
                font-size: 0.9rem;
            }
        }

        /* Navbar Sticky Progress Styles */
        .navbar-progress {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background-color: transparent;
            opacity: 0;
            transition: opacity 0.3s ease;
            pointer-events: none;
        }
        .navbar-progress.active {
            opacity: 1;
        }
        .navbar-progress-bar {
            height: 100%;
            background: linear-gradient(90deg, var(--primary-color), #34d399);
            border-top-right-radius: 4px;
            border-bottom-right-radius: 4px;
            box-shadow: 0 1px 4px rgba(16, 185, 129, 0.4);
            width: 0%;
            transition: width 1s ease;
        }
        .imagen-cuadrada {
  width: 100%;           /* O el ancho que necesites */
  aspect-ratio: 1 / 1;   /* Mantiene proporción 1:1 */
  object-fit: cover;      /* Ajusta la imagen sin aplastarla */
}
  /* Custom Modal Styles */
  .modal-content {
      border-radius: 1.25rem;
      border: none;
  }
  .modal-xl-custom {
      max-width: 1100px;
  }
  .list-group-item-action {
      transition: all 0.2s ease;
      border-left: 4px solid transparent;
  }
  .list-group-item-action:hover {
      background-color: #f8fafc;
      border-left-color: var(--primary-color);
      padding-left: 2rem !important;
  }
  .modal-header {
      border-bottom: 1px solid rgba(0,0,0,0.05);
      padding: 1.5rem;
  }
  .modal-footer {
      border-top: 1px solid rgba(0,0,0,0.05);
  }
  
  .empty-state {
      text-align: center;
      padding: 3rem 1rem;
      color: #94a3b8;
  }
  .empty-state i {
      font-size: 3rem;
      display: block;
      margin-bottom: 1rem;
      opacity: 0.5;
  }
  
  .cart-item {
      transition: transform 0.2s ease;
  }
  .cart-item:hover {
      transform: translateX(5px);
  }

  .reward-text{
    gap: 15px;
    display: flex;
    flex-direction: column;
    text-align: left;
  }

  /* Cart Flying Animation */
  .btn-add-to-car {
      position: relative;
  }
  .btn-add-to-car .cart-item-anim {
      position: absolute;
      height: 24px;
      width: 24px;
      top: -10px;
      right: -10px;
      display: none;
      z-index: 1000;
  }
  .btn-add-to-car .cart-item-anim:before {
      content: '1';
      display: block;
      line-height: 24px;
      height: 24px;
      width: 24px;
      font-size: 12px;
      font-weight: 600;
      background: #2bd156;
      color: white;
      border-radius: 20px;
      text-align: center;
  }
  .btn-add-to-car.sendtocart .cart-item-anim {
      display: block;
      animation: xAxis 1s forwards cubic-bezier(1.000, 0.440, 0.840, 0.165);
  }
  .btn-add-to-car.sendtocart .cart-item-anim:before {
      animation: yAxis 1s alternate forwards cubic-bezier(0.165, 0.840, 0.440, 1.000);
  }

  .btn-cart.shake {
      animation: shakeCart .4s ease-in-out forwards;
  }

  @keyframes xAxis {
      100% {
          transform: translateX(calc(100vw - 150px)); /* Rough estimate to top-right */
      }
  }

  @keyframes yAxis {
      100% {
          transform: translateY(calc(-100vh + 100px)); /* Rough estimate to top-right */
      }
  }

    @keyframes shakeCart {
      25% { transform: translateX(6px); }
      50% { transform: translateX(-4px); }
      75% { transform: translateX(2px); }
      100% { transform: translateX(0); }
  }

  /* Skeleton Loader Styles */
  .skeleton-card {
      background: white;
      border-radius: var(--radius-lg);
      overflow: hidden;
      box-shadow: var(--shadow-sm);
      height: 100%;
  }
  .skeleton-img {
      width: 100%;
      aspect-ratio: 1/1;
      background: #f3f4f6;
  }
  .skeleton-body {
      padding: 1.25rem;
  }
  .skeleton-text {
      height: 1rem;
      background: #f3f4f6;
      border-radius: 4px;
      margin-bottom: 0.5rem;
  }
  .skeleton-price {
      height: 1.5rem;
      width: 60%;
      background: #f3f4f6;
      border-radius: 4px;
      margin-bottom: 1rem;
  }
  .skeleton-button {
      height: 38px;
      background: #f3f4f6;
      border-radius: var(--radius-md);
  }
  .shimmer {
      position: relative;
      overflow: hidden;
  }
  .shimmer::after {
      content: "";
      position: absolute;
      top: 0;
      right: 0;
      bottom: 0;
      left: 0;
      background: linear-gradient(90deg, transparent, rgba(255,255,255,0.5), transparent);
      animation: shimmer 1.5s infinite;
  }
  @keyframes shimmer {
      0% { transform: translateX(-100%); }
      100% { transform: translateX(100%); }
  }


   .text-sm{
    font-size: 70% !important;
 }

 .text-xs{
    font-size: 60% !important;
 }
</style>
    
    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/fuse.js@6.6.2"></script>
    <script src="https://cdn.jsdelivr.net/npm/dexie@3.2.4/dist/dexie.min.js"></script>

</head>


<div id="toast-cart" class="toast-cart">
    <div class="toast-icon">
        <i class="bi bi-check-lg"></i>
    </div>
    <div class="toast-text">Agregado al carrito</div>
</div>

<body class="bg-light" data-user-logged-in="<?php echo isLoggedIn() ? 'true' : 'false'; ?>">
    <!-- Navbar -->
    <nav class="navbar navbar-custom fixed-top" id="navbar">
        <div class="container-fluid d-flex align-items-center justify-content-between px-3 px-md-5">
            <!-- Left: Logo -->
            <a class="navbar-brand" href="#">
                <i class="bi bi-shop-window text-success"></i>
                <span style="color: var(--primary-color);">iSeller</span> <span style="color: var(--text-primary);">Store</span>
            </a>

      
            <!-- Center: Search (Hidden on small mobile) -->
            <div class="d-none d-md-flex flex-grow-1 justify-content-center mx-4">
                <div class="header-search-container">
                    <i class="bi bi-search text-muted"></i>
                    <input type="text" class="header-search-input" id="search" placeholder="Buscar productos..." autocomplete="off">
                </div>
                <!-- Search Results Dropdown -->
                <div id="search-results" class="search-results-container"></div>
            </div>

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

                <button class="btn-icon" data-bs-toggle="modal" data-bs-target="#modalUbicacion" title="Nuestra Ubicación">
                    <i class="bi bi-geo-alt"></i>
                </button>


                <div class="dropdown">
                    <?php if (isLoggedIn()): ?>
                        <button class="btn-icon" data-bs-toggle="dropdown" title="Mi Cuenta">
                            <i class="bi bi-person"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end custom-dropdown-menu">
                            <li><h6 class="dropdown-header"><?php echo htmlspecialchars(getUserName()); ?></h6></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="perfil.php"><i class="bi bi-person-circle me-2"></i> Mi Perfil</a></li>
                            <li><a class="dropdown-item" href="checkout.php"><i class="bi bi-cart-check me-2"></i> Checkout</a></li>
                            <li><a class="dropdown-item" href="logout.php"><i class="bi bi-box-arrow-right me-2"></i> Cerrar Sesión</a></li>
                        </ul>
                    <?php else: ?>
                        <a href="login.php" class="btn-icon" title="Iniciar Sesión">
                            <i class="bi bi-person"></i>
                        </a>
                    <?php endif; ?>
                </div>

                <div class="cart-container">
                    <button class="btn-icon btn-cart position-relative" id="cartDropdown" data-bs-toggle="modal" data-bs-target="#modalCarrito">
                        <i class="bi bi-cart"></i>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger shadow-sm border border-light" id="cart-count">
                            0
                        </span>
                    </button>
                </div>

                <!-- Search Toggle Button (Mobile Only) -->
                <button class="btn-icon d-md-none" id="btn-search-toggle" title="Buscar">
                    <i class="bi bi-search"></i>
                </button>
            </div>
        </div>
        
        <!-- Mobile Search Overlay (Animated) -->
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

        <!-- Sticky Progress Bar (Hidden by default) -->
        <div id="navbar-progress-container" class="navbar-progress">
            <div class="navbar-progress-bar" style="width: 0%"></div>
        </div>
    </nav>
     
    <!-- Hero Banner (Dynamic) -->
    <section class="hero-section">
        <div class="hero-bg" id="hero-bg"></div>
        <div class="hero-overlay" id="hero-overlay"></div>
        <div class="hero-content py-3">
            <h1 class="hero-title text-white" id="hero-text"> ¡Compras gratis! Cada 5 niveles obtén $5 para tus compras. </h1>
            <p class="lead mb-4 text-white">Contamos con tienda fisica. <a data-bs-toggle="modal" data-bs-target="#modalUbicacion" class="text-white pointer">Consulta nuestra ubicación</a></p>

            <button class="btn rounded-pill px-4 py-2 text-white shadow-sm" style="background-color: rgb(111, 175, 122); border: none;" data-bs-toggle="modal" data-bs-target="#modalBeneficios">
                <i class="bi bi-star-fill me-2"></i> Ver Beneficios
            </button>



        </div>
    </section>

    <!-- Registration CTA -->
    <?php if (!isLoggedIn()): ?>
    <section class="py-2 border-bottom position-relative" style="background: linear-gradient(180deg, #f8fff9 0%, #ffffff 100%); z-index: 10;">
        <div class="container text-center">
            <h3 class="fw-bold mb-3" style="color: rgb(111, 175, 122);">¡Empieza con el pie derecho!</h2>
            <p class="lead mb-4 text-muted mx-auto" style="max-width: 700px;">
                Regístrate ahora y comienza tu camino hacia el ahorro inteligente. 
                <br>
                <span>¡Solo con registrarte ya recibirás un <strong style="color: rgb(111, 175, 122); font-size: 1.1em; font-weight: 800;">BONO DE $5</strong> para tus compras!</span>
            </p>
            <a href="registro.php" class="btn btn-lg rounded-pill px-5 mb-3 shadow text-white" style="background-color: rgb(111, 175, 122); border: none;">
                Crear mi cuenta gratis <i class="bi bi-arrow-right ms-2"></i>
            </a>
        </div>
    </section>
    <?php endif; ?>

    <!-- Rewards Progress Bar (Logged In Only) -->
    <?php if (isLoggedIn()): ?>
    <div class="rewards-bar-container" id="rewards-bar">
        <div class="container-fluid px-3 px-md-5">
            <div class="rewards-content">
                <!-- Mobile: Level Info Row -->
                <div class="level-info-row">
                    <div class="level-badge <?php echo ($loyaltyData['nivel'] % 5 == 0) ? 'pulse-badge' : ''; ?>">
                        <i class="bi bi-star-fill"></i> Nivel <?php echo $loyaltyData['nivel']; ?>
                    </div>
                    <!-- Mobile only info text -->
                    <div class="d-md-none small fw-bold text-success">
                        <?php echo number_format($loyaltyData['puntos'], 0); ?> pts
                    </div>
                </div>

                <!-- Progress Section -->
                <div class="progress-wrapper">
                    <div class="progress-text">
                        <span class="text-dark fw-semibold">
                            <?php if ($loyaltyData['falta'] <= 0): ?>
                                ¡Felicidades! Has completado este nivel
                            <?php else: ?>
                                Faltan <?php echo number_format($loyaltyData['falta'], 2); ?> pts para tu próxima recompensa
                            <?php endif; ?>
                        </span>
                        <span class="text-muted d-none d-md-inline" data-bs-toggle="tooltip" title="Acumula 10 puntos para subir de nivel">
                            <?php echo number_format($loyaltyData['progreso'], 2); ?> / 10
                        </span>
                    </div>
                    <div class="custom-progress" id="progress-bar">
                        <div class="progress-fill" style="width: 0%" data-width="<?php echo $loyaltyData['porcentaje']; ?>%"></div>
                    </div>
                </div>

                <!-- Reward Status / Desktop Info -->
                <div class="d-none d-md-block text-end" style="min-width: 200px;">
                    <?php if ($loyaltyData['nivel'] % 5 == 0): ?>
                        <div class="text-success fw-bold"><i class="bi bi-gift-fill me-1"></i> ¡Recompensa Desbloqueada!</div>
                    <?php elseif (($loyaltyData['nivel'] + 1) % 5 == 0): ?>
                        <div class="text-primary smaller"><i class="bi bi-trophy me-1"></i> Próximo nivel: Bonus $5</div>
                    <?php else: ?>
                        <div class="text-muted small">Sigue comprando para subir de nivel</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Product Grid Section -->
    <div class="container-fluid products-container px-3 px-md-5" id="products-container">
        <!-- Grid Header: Sorting -->
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
            <h2 class="fw-bold mb-0" style="color: var(--text-primary);">Nuestros Productos</h2>
            <div class="d-flex align-items-center gap-2">
                <span class="text-muted small fw-medium text-nowrap"><i class="bi bi-filter me-1"></i> Ordenar por:</span>
                <select class="form-select form-select-sm border-0 shadow-sm" id="sort-products" style="border-radius: var(--radius-md); min-width: 160px; height: 38px; cursor: pointer; background-color: white;">
                    <option value="" selected>Recomendados</option>
                    <option value="name_asc">Nombre (A-Z)</option>
                    <option value="name_desc">Nombre (Z-A)</option>
                </select>
            </div>
        </div>

        <div class="row g-4" id="products-grid">
            <!-- Products will be loaded here by JavaScript -->
             <!-- Skeleton placeholders will be shown here initially -->
             <script>
                 document.addEventListener('DOMContentLoaded', () => {
                     const grid = document.getElementById('products-grid');
                     if (grid && grid.children.length <= 1) { // Only if empty or has sentinel logic
                         let skeletons = '';
                         for (let i = 0; i < 24; i++) {
                             skeletons += `
                                 <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                                     <div class="skeleton-card">
                                         <div class="skeleton-img shimmer"></div>
                                         <div class="skeleton-body">
                                             <div class="skeleton-text shimmer" style="width: 80%;"></div>
                                             <div class="skeleton-price shimmer"></div>
                                             <div class="skeleton-button shimmer"></div>
                                         </div>
                                     </div>
                                 </div>
                             `;
                         }
                         grid.innerHTML = skeletons;
                     }
                 });
             </script>
        </div>
    </div>

    <!-- Modal Beneficios -->
    <div class="modal fade" id="modalBeneficios" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header text-white border-0" style="">
                    <h5 class="modal-title fw-bold"><i class="bi bi-gift-fill me-2"></i> Beneficios exclusivos</h5>
                    <button type="button" class="btn-close btn-close-dark" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4 p-lg-5">
                    <div class="row g-4">
                        <div class="col-12 text-center mb-3">
                            <h5 class="fw-bold text-success"><i class="bi bi-trophy me-2"></i>1. Tu fidelidad tiene recompensa</h5>
                            <p class="text-muted">Cada compra suma puntos. Acumula, sube de nivel y desbloquea descuentos permanentes.</p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="fw-bold text-dark"><i class="bi bi-calendar-event me-2"></i>2. Descuentos de Fin de Semana</h6>
                            <p class="small text-muted">Ofertas exclusivas cada sábado y domingo en productos nacionales.</p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="fw-bold text-success"><i class="bi bi-graph-up-arrow me-2"></i>3. Sube de nivel y gana más</h6>
                            <p class="small text-muted">Cada nivel te beneficia con descuentos, recompensas y bonos.</p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="fw-bold text-success"><i class="bi bi-stars me-2"></i>4. Recompensas monetarias</h6>
                            <p class="small text-muted">Cada 5 niveles adquiridos, obtendrás <b>$5</b> para gastar en productos.</p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="fw-bold text-dark"><i class="bi bi-lightning me-2"></i>5. Sin filas</h6>
                            <p class="small text-muted">Compra rápido desde tu móvil y ahorra tiempo.</p>
                        </div>
                    </div>
                    <?php if (!isLoggedIn()): ?>
                    <div class="mt-4 p-3 bg-light rounded text-center">
                        <p class="mb-2 fw-semibold">¿Listo para empezar a ganar?</p>
                        <a href="registro.php" class="btn btn-lg rounded-pill px-5 shadow text-white" style="background-color: rgb(111, 175, 122); border: none;">Crear cuenta gratis</a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Ubicación -->
    <div class="modal fade" id="modalUbicacion" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title text-white fw-bold"><i class="bi bi-geo-alt-fill me-2"></i> Nuestra Tienda Física</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-0">
                    <div class="p-4 bg-light shadow-sm">
                        <div class="row align-items-center">
                            <div class="col-lg-7">
                                <h6 class="fw-bold mb-1">Visítanos directamente</h6>
                                <p class="text-muted small mb-3">Estamos ubicados en una zona accesible y segura para tu comodidad.</p>
                                <div class="d-flex align-items-start gap-3 mb-2">
                                    <i class="bi bi-pin-map text-success fs-5"></i>
                                    <div>
                                        <p class="mb-0 fw-semibold">Dirección (YolaMarket)</p>
                                        <p class="text-muted small mb-0">Urb Simón Bolívar, Calle principal, diagonal a la 52 brigada - YolaMarket</p>
                                    </div>
                                </div>
                                <div class="d-flex align-items-start gap-3">
                                    <i class="bi bi-clock text-success fs-5"></i>
                                    <div>
                                        <p class="mb-0 fw-semibold">Horario de Atención</p>
                                        <p class="text-muted small mb-0">Lunes a Sábado: 8:00 AM - 12:30 PM / 3:00 PM - 9:00 PM<br>Domingos: 9:00 AM - 12:30 PM</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-5 d-none d-lg-block text-center">
                                <i class="bi bi-shop text-success" style="font-size: 5rem; opacity: 0.2;"></i>
                            </div>
                        </div>
                    </div>
                    <div id="map-tienda" style="height: 400px; width: 100%;"></div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Cerrar</button>
                    <a href="https://www.google.com/maps?q=YolaMarket - Urb Simón Bolívar" target="_blank" class="btn btn-success px-4">
                        <i class="bi bi-map me-2"></i> Abrir en Google Maps
                    </a>
                </div>
            </div>
        </div>
    </div>
    <!-- Modal Recompensas -->
    <div class="modal fade" id="modalRecompensas" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title fw-bold text-white"><i class="bi bi-gift-fill me-2"></i> Mis Recompensas</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4 text-center">
                    <div id="reward-text" class="fs-5 d-flex flex-column gap-3"></div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Cuenta -->
    <div class="modal fade" id="modalCuenta" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold"><i class="bi bi-person-fill me-2"></i> Mi Cuenta</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-0">
                    <div class="p-3 border-bottom bg-light">
                        <h6 class="mb-0 fw-bold"><?php echo htmlspecialchars(getUserName()); ?></h6>
                    </div>
                    <div class="list-group list-group-flush">
                        <a href="perfil.php" class="list-group-item list-group-item-action py-3">
                            <i class="bi bi-person-circle me-3 text-primary"></i> Mi Perfil
                        </a>
                        <a href="checkout.php" class="list-group-item list-group-item-action py-3">
                            <i class="bi bi-cart-check me-3 text-primary"></i> Mi Ordenes / Checkout
                        </a>
                        <a href="logout.php" class="list-group-item list-group-item-action py-3 text-danger">
                            <i class="bi bi-box-arrow-right me-3"></i> Cerrar Sesión
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Carrito -->
    <div class="modal fade" id="modalCarrito" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold"><i class="bi bi-bag-fill me-2"></i> Mi Carrito</h5>
                    <button type="button" class="btn-close btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-0">
                    <div id="cart-items" class="p-3" style="max-height: 400px; overflow-y: auto;">
                        <!-- Items inserted via JS -->
                        <div class="empty-state">
                            <i class="bi bi-cart-x"></i>
                            <p>Tu carrito está vacío</p>
                    </div>
                    </div>
                    <div id="cart-footer" class="border-top p-3 bg-light hide">
                        <div class="d-flex justify-content-between mb-3 align-items-end">
                            <span class="text-muted">Total:</span>
                            <div class="text-end line-height-1">
                                <div class="fs-4 fw-bold text-success"><span id="cart-total-dolar">$0.00</span></div>
                                <div class="small text-muted"><span id="cart-total-bs">Bs 0.00</span></div>
                            </div>
                        </div>
                        <div class="d-grid gap-2">
                            <a href="checkout.php" class="btn btn-primary btn-lg">
                                Ir a Pagar <i class="bi bi-arrow-right ms-2"></i>
                            </a>
                            <button class="btn btn-outline-danger btn-sm border-0" onclick="vaciarCarritoJs()">
                                Vaciar Carrito
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Product Detail Modal -->
    <div class="modal fade" id="modalProductoDetalle" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered product-modal-dialog">
            <div class="modal-content product-modal-content">
                <button type="button" class="btn-close btn-close-white product-modal-close" data-bs-dismiss="modal" aria-label="Close">
                    <i class="bi bi-x-lg"></i>
                </button>
                <div class="row g-0">
                    <!-- Image Side (Protruding) -->
                    <div class="col-md-5">
                        <div class="product-modal-img-container">
                             <img src="" id="modal-product-img" class="product-modal-img" alt="Product">
                        </div>
                    </div>
                    <!-- Details Side -->
                    <div class="col-md-7">
                        <div class="product-modal-body">
                            <div class="mb-4">
                                <h2 class="modal-product-title" id="modal-product-name">Product Name</h2>
                                <div class="modal-product-code">COD: <span id="modal-product-code">12345</span></div>
                            </div>
                            
                            <div class="pricing-section mb-4">
                                <div class="modal-product-price-main">
                                    <span class="modal-product-currency">$</span><span id="modal-product-price-d">0.00</span>
                                </div>
                                <div class="modal-product-price-secondary">
                                    <i class="bi bi-wallet2"></i> Bs <span id="modal-product-price-bs">0.00</span>
                                </div>
                            </div>

                            <div class="modal-section-title">DETALLES Y BENEFICIOS</div>
                            <ul id="modal-product-description" class="modal-benefits-list">
                                <!-- Bullet points inserted via JS -->
                            </ul>

                            <div class="modal-actions-container">
                                <div class="modal-qty-wrapper">
                                    <button class="btn-qty" onclick="changeModalQty(-1)"><i class="bi bi-dash"></i></button>
                                    <input type="number" class="qty-input" id="modal-product-qty" value="1" min="1" readonly>
                                    <button class="btn-qty" onclick="changeModalQty(1)"><i class="bi bi-plus"></i></button>
                                </div>

                                <button class="btn modal-btn-add" id="modal-btn-add"
                                    data-add-id="" data-codigo="" data-P_D="" data-P_P="" data-P_B="">
                                    <i class="bi bi-cart-plus-fill"></i> AGREGAR AL CARRITO
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- jQuery -->
    <script src="assets/js/jquery.min.js"></script>
    <!-- Bootstrap Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom Scripts -->
    <script src="assets/js/fastclick.js"></script>
    <script src="assets/js/nprogress.js"></script>
    <script src="assets/js/custom.js"></script>
    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
    <!-- Notiflix Library -->
    <script src="assets/dist/notiflix-Notiflix-67ba12d/dist/notiflix-aio-3.2.8.min.js"></script>

    <script>


        const urlParams = new URLSearchParams(window.location.search);
        const ref = urlParams.get('ref');

        if (ref) {
            document.cookie = `referral_code=${ref}; path=/; max-age=2592000`;
        }



        var productos = []; // Se cargará dinámicamente
        var productos_por_id = {}; // Se llenará a medida que se haga scroll o busqueda
        var codigos = [];

        const base_url = 'core/';

        // Initialize UI Elements
        document.addEventListener('DOMContentLoaded', () => {
            // Animate Rewards Bar
            const progBar = document.querySelector('.rewards-bar-container .progress-fill');
            const percent = progBar ? progBar.getAttribute('data-width') : '0%';
            
            if (progBar) {
                setTimeout(() => {
                    progBar.style.width = percent;
                }, 300);
            }
            
            // Sync Navbar Progress
            const navProgBar = document.querySelector('.navbar-progress-bar');
            if(navProgBar) navProgBar.style.width = percent;
            
            // Init Tooltips
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl)
            })

            // --- MOBILE SEARCH OVERLAY LOGIC ---
            const searchToggle = document.getElementById('btn-search-toggle');
            const searchOverlay = document.getElementById('mobile-search-overlay');
            const searchClose = document.getElementById('btn-search-close');
            const searchInputMobile = document.getElementById('search-mobile');

            if (searchToggle && searchOverlay) {
                searchToggle.addEventListener('click', (e) => {
                    e.stopPropagation();
                    searchOverlay.classList.add('active');
                    setTimeout(() => searchInputMobile.focus(), 100);
                });

                if (searchClose) {
                    searchClose.addEventListener('click', () => {
                        searchOverlay.classList.remove('active');
                        $('#search-results-mobile').removeClass('show');
                    });
                }

                // Close on click outside the overlay content
                document.addEventListener('click', (e) => {
                    if (searchOverlay.classList.contains('active') && 
                        !searchOverlay.contains(e.target) && 
                        !searchToggle.contains(e.target)) {
                        searchOverlay.classList.remove('active');
                        $('#search-results-mobile').removeClass('show');
                    }
                });
            }

            // --- STICKY PROGRESS OBSERVER ---
            const rewardsBar = document.getElementById('rewards-bar');
            const navProgress = document.getElementById('navbar-progress-container');
            const navbar = document.getElementById('navbar');

            if (rewardsBar && navProgress && navbar) {
                const navHeight = navbar.offsetHeight;
                
                const observer = new IntersectionObserver((entries) => {
                    entries.forEach(entry => {
                        // Si el rewards bar NO está intersectando Y su posición top es negativa (pasó hacia arriba)
                        if (!entry.isIntersecting && entry.boundingClientRect.top < navHeight) {
                            navProgress.classList.add('active');
                        } else {
                            navProgress.classList.remove('active');
                        }
                    });
                }, {
                    root: null, // Viewport
                    rootMargin: `-${navHeight}px 0px 0px 0px`, // Offset por el navbar fijo
                    threshold: 0
                });

                observer.observe(rewardsBar);
            }

            // --- CHECK REWARDS API ---
            checkAvailableRewards();

            // --- INIT STORE MAP ON MODAL SHOW ---
            const modalUbicacion = document.getElementById('modalUbicacion');
            let mapTienda = null;

            modalUbicacion.addEventListener('shown.bs.modal', function () {
                if (!mapTienda) {
                    // Coordenadas de ejemplo para Caracas

                    const lat = 5.642498;
                    const lng = -67.602170; 



                    mapTienda = L.map('map-tienda').setView([lat, lng], 17);

                    googleHybrid = L.tileLayer('http://{s}.google.com/vt/lyrs=s,h&x={x}&y={y}&z={z}',{
                            maxZoom: 20,
                            subdomains:['mt0','mt1','mt2','mt3']
                    });

                    googleHybrid.addTo(mapTienda);

                    L.marker([lat, lng]).addTo(mapTienda)
                        .bindPopup('<b>iSeller Store</b><br>Nuestra Tienda Física.')
                        .openPopup();
                } else {
                    // Refrescar tamaño para corregir problemas de renderizado en modales
                    mapTienda.invalidateSize();
                }
            });
        });

        async function checkAvailableRewards(type = null) {
            try {
                const res = await fetch('api/recompensas.php');
                const data = await res.json();
                console.log(data)
                if(data.success && data.has_rewards) {
                    // Update UI to show rewards available
                    const rewardsContainer = document.querySelector('#reward-dropdown');
                    if(rewardsContainer) {
                        rewardsContainer.classList.remove('hide');
                        document.querySelector('#badge-reward').innerHTML = data.count;
                            if(data.init_reward === true) {
                            Notiflix.Report.success(
                                '¡Recompensas obtenidas!',
                                'Tienes disponible <b>$5</b> que podrás usar a partir del nivel 5. Tambien obtuviste un descuento especial para tu próxima compra.',
                                'Entendido',
                                () => {
                                    $('#modalBeneficios').modal('show');
                                }
                            );
                            }

                        // recorre las recompensas para mostrarlas en el modal
                        for (let i = 0; i < data.count; i++) {
                            const reward = data.rewards[i];

                            let rewardText = '';

                            if (reward.tipo === 'referido') {
                                // Texto especial para referidos
                                rewardText = `<span>🤝 ¡Buen trabajo! Has recibido <b>Puntos</b> por invitar a un amigo.</span>`;
                            } else if (reward.tipo === 'monetaria') {
                                // Texto existente para recompensas monetarias
                                if (reward.nivel_desbloqueo < 5) {
                                    rewardText = `<span>🎉 ¡Felicidades! Has obtenido <b>$${reward.monto}</b> por registrarte, podrás usarlo a partir del nivel 5.</span>`;
                                } else {
                                    rewardText = `<span>🎉 ¡Felicidades! Has desbloqueado <b>$${reward.monto}</b> por completar nivel <b>${reward.nivel_desbloqueo}</b></span>`;
                                }
                            } else {
                                // Texto existente para otros tipos (descuentos, etc.)
                                rewardText = `<span>🎁 ¡Sorpresa! Has conseguido un <b>descuento especial</b> para tu próxima compra.</span>`;

                            }

                            // Agregar el texto al contenedor
                            document.querySelector('#reward-text').innerHTML += rewardText;
                        }
                        }
                    }
                } catch(e) {
                    console.error("Error checking rewards", e);
                }
            }

        /* buscador de productos */
        let productos_indexados = [];
        let fuse;
        
        // Cargar índice de búsqueda (ligero)
        fetch('api/productos.php?mode=search_index')
            .then(res => res.json())
            .then(data => {
                productos_indexados = data.searchIndex;
                // Mapear claves para Fuse (revertir minificación)
                const searchItems = data.searchIndex.map(item => ({
                    id: item.id,
                    nombre: item.n, // 'n' es nombre
                    codigo: item.c,
                    stock: item.s,
                    mayor: item.m,
                    precio_dolar_visible: item.pd,
                    precio_peso_visible: item.pp,
                    precio_bs_visible: item.pb
                }));
                
                fuse = new Fuse(searchItems, {
                    keys: ['nombre'],
                    threshold: 0.28,
                    ignoreLocation: true,
                    includeScore: false,
                    useExtendedSearch: false
                });
            })
            .catch(err => console.error("Error cargando índice de búsqueda", err));




        window.addEventListener("scroll", () => {
            const scrolled = window.scrollY;
            const heroBg = document.getElementById("hero-bg");

            heroBg.style.transform = `translateY(${scrolled * 0.4}px)`;
        });
        /**
         * ==========================================
         *  HERO BANNER LOGIC & SCROLL ANIMATION
         * ==========================================
         */
        // Slider de Texto
        const heroTexts = [
            "¡Compra y gana! Acumula puntos y desbloquea descuentos",
        "¿Necesitas hacerle el mercado a un familiar? Agrega su dirección y lo llevamos directo a su puerta",
            "Fines de Semana de Ahorro: ofertas exclusivas",
            "Sube de Nivel: gana recompensas en efectivo",
            "Tu mercado en casa o en tienda: delivery confiable"
        ];
        let heroTextIndex = 0;
        const heroTextElement = document.getElementById('hero-text');
        
        setInterval(() => {
            heroTextElement.style.opacity = '0';
            heroTextElement.style.transform = 'translateY(20px)';
            
            setTimeout(() => {
                heroTextIndex = (heroTextIndex + 1) % heroTexts.length;
                heroTextElement.innerText = heroTexts[heroTextIndex];
                heroTextElement.style.opacity = '1';
                heroTextElement.style.transform = 'translateY(0)';
            }, 500); // Wait for fade out
        }, 5000); // Change every 5 seconds

        // Scroll Animation
        window.addEventListener('scroll', () => {
            const scrollY = window.scrollY;
            const heroBg = document.getElementById('hero-bg');
            const heroContent = document.querySelector('.hero-content');
            
            // Parallax & Fade Effect
            if (scrollY < 600) {
                // Fade out content
                const opacity = 1 - (scrollY / 400);
                if(heroContent) heroContent.style.opacity = Math.max(opacity, 0);
                
                // Transform BG to white/fade
                if(heroBg) {
                    // heroBg.style.filter = `blur(${scrollY / 50}px) grayscale(${scrollY / 10}%)`;
                    // Optional: Fade overlay to white
                }
            }
        });

        // Función de búsqueda rápida y limpia
     /*   const buscarConFuse = termino => {
            if(!fuse) return [];
            return fuse.search(`=${termino}`).map(r => r.item);
        };
*/

        // Función de búsqueda rápida y limpia
        const buscarConFuse = termino => fuse.search(`=${termino}`).map(r => r.item);
        /* buscador de productos */

        // Search functionality for both desktop and mobile
        $(document).on('keyup', '#search, #search-mobile', function() {
            var nombreProducto = $(this).val();
            var isMobile = $(this).attr('id') === 'search-mobile';
            var resultsContainer = isMobile ? '#search-results-mobile' : '#search-results';
            
            if (nombreProducto.length > 2) {
                let resultados = buscarConFuse(nombreProducto)
                mostrarResultadosBusqueda(resultados, resultsContainer)
            } else {
                $(resultsContainer).html('').removeClass('show');
            }
        });

        // Close search results when clicking outside
        $(document).on('click', function(e) {
            if (!$(e.target).closest('#search, #search-results, #search-mobile, #search-results-mobile').length) {
                $("#search-results, #search-results-mobile").removeClass('show');
            }
        });

        function mostrarResultadosBusqueda(resultados, containerSelector = '#search-results') {
            const searchResults = $(containerSelector);
            searchResults.html('');
            
            if (!Array.isArray(resultados) || resultados.length === 0) {
                searchResults.html('<div class="p-3 text-muted text-center"><i class="bi bi-search me-2"></i>No se encontraron productos</div>');
                searchResults.addClass('show');
                return;
            }

            let html = '<div class="list-group list-group-flush">';
            resultados.slice(0, 8).forEach(item => {
                const rest = (item.mayor === '1' ?
                    '<span class="badge bg-success-subtle text-success border border-success-subtle">Mayor</span>' :
                    `<span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">Stock: ${item.stock}</span>`);

                html += `
                    <div class="list-group-item list-group-item-action border-0 border-bottom py-3" style="cursor: pointer;" onclick="seleccionarProductoBusqueda('${item.id}')">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="flex-grow-1">
                                <h6 class="mb-1 fw-semibold text-primary-dark">${item.nombre}</h6>
                                <div class="d-flex align-items-center gap-2 mb-1">
                                    ${rest}
                                </div>
                                <div class="d-flex gap-3 small">
                                    <span class="fw-bold text-success">
                                        $${formatNumber(item.precio_dolar_visible)}
                                    </span>
                                    <span class="text-muted border-start ps-3">
                                        ${formatNumber(recortarADosDecimales(item.precio_bs_visible))} Bs
                                    </span>
                                </div>
                            </div>
                            <div class="d-flex align-items-center gap-2" >
                                <div class="quantity-control form-control-sm border px-1">
                                    <input type="number" class="qty-input form-control-plaintext p-0 text-center" 
                                        data-cantidad-id="${item.id}" value="1" style="width: 40px; height: 30px;">
                                </div>
                                <button class="btn btn-sm btn-action-primary d-flex align-items-center justify-content-center btn-add-to-car" 
                                    style="width: 38px; height: 38px; border-radius: 50%; background: var(--primary-color); color: white; border: none;"
                                    data-add-id="${item.id}"
                                    data-codigo="${item.codigo || ''}"
                                    data-P_D="${item.precio_dolar_visible}"
                                    data-P_P="${item.precio_peso_visible}"
                                    data-P_B="${item.precio_bs_visible}">
                                    <i class="bi bi-plus-lg"></i>
                                    <span class="cart-item-anim"></span>
                                </button>
                            </div>
                        </div>
                    </div>
                `;
            });
            html += '</div>';
            searchResults.html(html);
            searchResults.addClass('show');
        }

        function seleccionarProductoBusqueda(id) {
           // Optional: Navigate to product detail or highlight it
        }

        // Infinite Scroll & Sorting Logic
        let currentPage = 1;
        let isLoading = false;
        let hasMore = true;
        let currentSort = ''; // Sorting parameter
        const productsGrid = document.getElementById("products-grid");

        // Event listener for sorting dropdown
        document.getElementById('sort-products').addEventListener('change', function() {
            currentSort = this.value;
            currentPage = 1;
            hasMore = true;
            
            // Show skeletons to preserve vertical space and prevent scroll jump
            showSkeletons();
            
            cargarMasProductos();
        });

        function showSkeletons() {
            let skeletons = '';
            for (let i = 0; i < 24; i++) {
                skeletons += `
                    <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                        <div class="skeleton-card">
                            <div class="skeleton-img shimmer"></div>
                            <div class="skeleton-body">
                                <div class="skeleton-text shimmer" style="width: 80%;"></div>
                                <div class="skeleton-price shimmer"></div>
                                <div class="skeleton-button shimmer"></div>
                            </div>
                        </div>
                    </div>
                `;
            }
            productsGrid.innerHTML = skeletons;
        }
        const sentinel = document.createElement("div"); // Elemento invisible para detectar el final
        sentinel.id = "sentinel";
        document.getElementById("products-container").appendChild(sentinel);

     async function cargarMasProductos() {
            if (isLoading || !hasMore) return;
            isLoading = true;

            let loaderShown = false;

            // Programar loader después de 1s
            const loaderTimer = setTimeout(() => {
                Notiflix.Loading.standard('Cargando productos...');
                loaderShown = true;
            }, 1000);
            try {
                const res = await fetch(`api/productos.php?mode=grid&page=${currentPage}&limit=24&order=${currentSort}`);
                const data = await res.json();

                const recompensas = data.recompensas[0];

                if (data.data && data.data.length > 0) {
                    renderProductos(data.data, recompensas);

                    data.data.forEach(p => {
                        productos_por_id[p.id] = p;
                    });

                    currentPage++;
                    hasMore = data.hasMore;
                } else {
                    hasMore = false;
                }

            } catch (err) {
                console.error("Error cargando productos", err);

            } finally {
                // Cancelar aparición del loader si aún no salió
                clearTimeout(loaderTimer);

                // Quitar loader solo si llegó a mostrarse
                if (loaderShown) {
                    Notiflix.Loading.remove();
                }

                isLoading = false;
            }
        }


        function renderProductos(listaProductos, recompensas) {
            // Remove spinner temporarily to append before it, or just append to grid
            
            // Limpiar skeletons o spinner si es la primera carga
            if(currentPage === 1) {
                 productsGrid.innerHTML = '';
            }

            listaProductos.forEach(producto => {
               
                const isOutOfStock = producto.stock <= 0 && producto.mayor !== '1';
                const opacityClass = isOutOfStock ? 'opacity-50 grayscale' : '';
                const btnDisabled = isOutOfStock ? 'disabled btn-disabled' : '';
                const btnText = isOutOfStock ? 'Agotado' : '';


                let precio_dolar_visible = producto.precio_dolar_visible;
                let precio_bs_visible = producto.precio_bs_visible;
                
                // Calcula precio final
                let precio_dolar = '$' + formatNumber(recortarADosDecimales(precio_dolar_visible));
                let precio_bs = 'Bs' + formatNumber(recortarADosDecimales(precio_bs_visible));

                    if (recompensas) {
                        const tipo_recompensa = recompensas.tipo;

                        if (tipo_recompensa === 'descuento_ganancia') {
                            precio_dolar_visible = producto.costo_dolar;
                            precio_bs_visible = producto.costo_bs;
                            precio_dolar = `$${formatNumber(recortarADosDecimales(producto.costo_dolar))} <span class="text-decoration-line-through text-danger text-xs">${precio_dolar}</span> `;
                            precio_bs = `Bs ${formatNumber(recortarADosDecimales(producto.costo_bs))} <span class="text-decoration-line-through text-danger text-sm">${precio_bs}</span> `;
                        }
                    }


                // Preparing Image source for modal interaction
                const imgSrc = producto.img != '' ? producto.img : `https://placehold.co/400x400/f3f4f6/a3a3a3?text=${producto.nombre.substring(0,2)}`;

                let card = `
                    <div class="col-12 col-sm-6 col-md-4 col-lg-3 product-item">
                        <div class="product-card ${opacityClass}">
                            <div class="product-img-wrapper" style="cursor: pointer;" onclick="openProductModal('${producto.id}')">`;
                            card += `<img src="${imgSrc}" 
                                     loading="lazy"
                                     class="product-img imagen-cuadrada" alt="${producto.nombre}">`;
                            card += `</div>
                            <div class="product-body">
                      
                                <h3 class="product-title" title="${producto.nombre}">${producto.nombre}</h3>
                                
                                <div class="price-container">
                                    <div class="price-main"><span class="price-dolar">${precio_dolar}</span></div>
                                    <div class="price-sub small text-muted"><span class="price-bs">${precio_bs}</span></div>
                                </div>

                                <div class="card-actions">
                                    <div class="quantity-control">
                                        <button class="btn-qty" onclick="changeQty(this, -1)">-</button>
                                        <input type="number" class="qty-input" 
                                            data-cantidad-id="${producto.id}" value="1" min="1" readonly>
                                        <button class="btn-qty" onclick="changeQty(this, 1, ${producto.stock})">+</button>
                                    </div>
                                    <button style="height: 37px;" class="btn btn-sm btn-add btn-add-to-car ${btnDisabled}" 
                                        data-add-id="${producto.id}"
                                        data-codigo="${producto.codigo || ''}"
                                        data-P_D="${precio_dolar_visible}"
                                        data-P_B="${precio_bs_visible}">
                                        <i class="bi bi-cart-plus"></i> ${btnText}
                                        <span class="cart-item-anim"></span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>`;
                $("#products-grid").append(card);
            });
        }
        // --- NEW MODAL LOGIC ---

        function openProductModal(productId) {
            const producto = productos_por_id[productId];
            if (!producto) return;

            // Populate Elements
            document.getElementById('modal-product-name').textContent = producto.nombre;
            document.getElementById('modal-product-code').textContent = producto.codigo || 'N/A';
            document.getElementById('modal-product-price-d').textContent = formatNumber(producto.precio_dolar_visible);
            document.getElementById('modal-product-price-bs').textContent = formatNumber(producto.precio_bs_visible);

            // Image
            const imgSrc = producto.img != '' ? producto.img : `https://placehold.co/400x400/f3f4f6/a3a3a3?text=${producto.nombre.substring(0,2)}`;
            document.getElementById('modal-product-img').src = imgSrc;

            // Reset Qty
            document.getElementById('modal-product-qty').value = 1;

            // Update Add to Cart Button
            const btn = document.getElementById('modal-btn-add');
            btn.dataset.addId = producto.id;
            btn.dataset.codigo = producto.codigo || '';
            btn.dataset.P_D = producto.precio_dolar_visible;
            btn.dataset.P_P = producto.precio_peso_visible;
            btn.dataset.P_B = producto.precio_bs_visible;

            // Benefits / Description Logic
            const descList = document.getElementById('modal-product-description');
            descList.innerHTML = '';
            
            // Try to use description if available, otherwise generic benefits or nothing
            if (producto.descripcion) {
                // Split by newlines or bullets if structured
                 const lines = producto.descripcion.split(/\r?\n|•|- /).filter(line => line.trim() !== '');
                 if(lines.length > 0) {
                     lines.forEach(line => {
                         descList.innerHTML += `<li>${line.trim()}</li>`;
                     });
                 } else {
                     descList.innerHTML += `<li>${producto.descripcion}</li>`;
                 }
            } else {
                // Default generic benefits if empty
                descList.innerHTML = `
                    <li>Alta calidad garantizada</li>
                    <li>Disponibilidad inmediata</li>
                    <li>Mejor relación precio-valor</li>
                `;
            }

            // Show Modal
            const modal = new bootstrap.Modal(document.getElementById('modalProductoDetalle'));
            modal.show();
        }

        function changeModalQty(delta) {
            const input = document.getElementById('modal-product-qty');
            let val = parseInt(input.value) || 1;
            val += delta;
            
            // Logic to check max stock could be added here if we had current stock handy easily in scope
            // For now, simpler check
            if (val < 1) val = 1;
            
            // We can check stock from the current button data if needed, but let's assume loose check or add param
            // const btn = document.getElementById('modal-btn-add');
            // const pid = btn.dataset.addId;
            // const product = productos_por_id[pid];
            // if (product && val > product.stock && product.mayor != '1') val = product.stock;

            input.value = val;
            
            // Sync with the specific product card input if we want seamless experience?
            // Not strictly required but nice. Leaving simple for now.
        }

        // Override the global click listener for the modal button to use specific input
        // Actually the global listener `document.addEventListener('click'...` handles `.btn-add-to-car`
        // It looks for `input[data-cantidad-id="${id}"]`. 
        // Our modal input DOES NOT have `data-cantidad-id`.
        // So we need to tweak `addtocarJS` OR the click listener.
        
        // Let's modify the click listener or `addtocarJS` to accept an explicit quantity value fallback.
        // `addtocarJS` already accepts `cantidad_scann`. We can use that.
        
        // Let's modify the modal add button onclick to call addtocarJS directly instead of relying on the generic listener,
        // OR modify the generic listener to handle the modal context.
        // The generic listener finds ".btn-add-to-car". Our modal button HAS this class.
        // It then calls `addtocarJS(..., null, null, btn)`.
        // Inside `addtocarJS`: `const inputCantidad = document.querySelector('input[data-cantidad-id="${id}"]');`
        // It will find the GRID input. This is actually fine! It will add the grid ID's quantity.
        // BUT we want the MODAL's quantity.
        
        // FIX: Remove `.btn-add-to-car` class from the Modal button and give it a unique listener or ID,
        // OR update the generic listener.
        // Updating generic listener is risky for regressions.
        // Better: specific listener for modal add button.
        
        // I'll add an ID listener for `modal-btn-add` at the end of this block.

        // Intersection Observer
        const observer = new IntersectionObserver((entries) => {
            if (entries[0].isIntersecting) {

                cargarMasProductos();
            }
        }, { rootMargin: "200px" });

        observer.observe(sentinel);

        // Cargar primera pagina
        // cargarMasProductos(); // El observer lo llamará automáticamente al estar visible el sentinel vacío


        // Helper for quantity buttons
        window.changeQty = function(btn, delta, max = null) {
            const input = btn.parentNode.querySelector('input');
            let val = parseInt(input.value) || 1;
            val += delta;
            if(val < 1) val = 1;
            if(max != null && val > max) val = max;
            input.value = val;
        };

        var total_pesos = 0;
        var total_dolares = 0;
        var total_bolivares = 0;

        function confirmarVenta(tipo = 'venta') {
            if (!carritoActivo || Object.keys(carritoActivo).length === 0) {
                return false;
            }
            $('button').blur();
            procesarPedido(6, 1)
        }

        document.addEventListener('click', function(event) {
            const btn = event.target.closest('.btn-add-to-car');
            if (btn) {
                let id_p = btn.getAttribute('data-add-id');
                let dolarventa_p = btn.getAttribute('data-P_D')
                let pesoventa_p = btn.getAttribute('data-P_P')
                let bolivarventa_p = btn.getAttribute('data-P_B')
                
                $('#search, #search-mobile').val('')
                $("#search-results, #search-results-mobile").removeClass('show');
                
                addtocarJS(id_p, dolarventa_p, pesoventa_p, bolivarventa_p, null, null, btn);
            }
        });

        // * IndexedBD //
        const db = new Dexie("POS_DB");

        db.version(2).stores({
            carritoActivo: 'id',
            carritosVenta: 'id',
            carritosReservados: 'id',
            cart_meta: 'id'
        });
        // * IndexedBD //

        let carritoActivo = {};

        // Listen for Modal Add to Cart
        document.getElementById('modal-btn-add').addEventListener('click', function() {
            const btnMod = this;
            const id = btnMod.dataset.addId;
            const qty = document.getElementById('modal-product-qty').value;
            
            if(!id) return;

            // 1. Sincronizar cantidad en todos los inputs visibles de este producto
            document.querySelectorAll(`input[data-cantidad-id="${id}"]`).forEach(input => {
                input.value = qty;
            });

            // 2. Buscar el botón de agregar original (en grilla o buscador)
            // Priorizamos el de la grilla si existe, sino cualquier otro
            const originalBtn = document.querySelector(`.btn-add-to-car[data-add-id="${id}"]`);
            
            if (originalBtn) {
                // Simular click en el botón original
                originalBtn.click();
                bootstrap.Modal.getInstance(document.getElementById('modalProductoDetalle')).hide();
            } else {
                // Fallback: Si no se encuentra el botón físico (ej: producto no renderizado aún), 
                // llamar directamente a la función
                addtocarJS(
                    id, 
                    btnMod.dataset.p_d, 
                    btnMod.dataset.p_p, 
                    btnMod.dataset.p_b, 
                    null, 
                    qty, 
                    btnMod
                );
            }
        });

        async function updateCartTimestamp() {
            await db.cart_meta.put({ id: 'last_updated', timestamp: Date.now() });
        }

        async function checkCartExpiration() {
            const meta = await db.cart_meta.get('last_updated');
            if (meta) {
                const now = Date.now();
                const diff = now - meta.timestamp;
                const limit = 20 * 60 * 1000; // 20 minutes in ms

                if (diff > limit) {
                    await db.carritoActivo.clear();
                    await db.cart_meta.delete('last_updated');
                    return true; // Expired
                }
            }
            return false;
        }

        // Cargar carrito activo desde IndexedDB al iniciar
        (async function cargarCarritoInicial() {
            await checkCartExpiration();
            
            // Ensure skeletons are shown while loading initial products
            showSkeletons();
            
            const items = await db.carritoActivo.toArray();
            carritoActivo = items.reduce((obj, item) => {
                obj[item.id] = item;
                return obj;
            }, {});
            actualizarCarritoJs();
            cargarMasProductos();
        })();

        async function actualizarCarritoActivo() {
            const items = await db.carritoActivo.toArray();
            carritoActivo = items.reduce((obj, item) => {
                obj[item.id] = item;
                return obj;
            }, {});
            actualizarCarritoJs();
        }


         let toastTimeout;

        function showToastCart(message = 'Agregado al carrito') {
            const toast = document.getElementById('toast-cart');
            const text = toast.querySelector('.toast-text');

            text.textContent = message;

            clearTimeout(toastTimeout);

            // Reset
            toast.classList.remove('expand', 'show');

            // Paso 1: mostrar icono
            setTimeout(() => {
                toast.classList.add('show');
            }, 10);

            // Paso 2: expandir
            setTimeout(() => {
                toast.classList.add('expand');
            }, 300);

            // Paso 3: ocultar
            toastTimeout = setTimeout(() => {
                toast.classList.remove('expand');
                setTimeout(() => {
                    toast.classList.remove('show');
                }, 300);
            }, 2800);
        }

        async function verificarStock(id, cantidad) {
            // Mostrar Loader global o especifico
            Notiflix.Loading.standard('Verificando disponibilidad...');
            
            try {
                const res = await fetch(`api/check_stock.php?id=${id}&cantidad=${cantidad}`);
                const data = await res.json();
                
                Notiflix.Loading.remove();
                
                if (data.success) {
                    return {'result' : true, 'message' : 'Disponible'};
                } else {
                    return {'result' : false, 'message' : 'No hay stock suficiente'};
                }
            } catch (e) {
                Notiflix.Loading.remove();
                return {'result' : false, 'message' : 'Problemas de internet, intente nuevamente'}; // Asumir no disponible si hay error de red para seguridad
            }
        }

        async function addtocarJS(id, dolarventa_p, pesoventa_p, bolivarventa_p, mayor, cantidad_scann = null, btnElement = null) {
            const inputCantidad = document.querySelector(`input[data-cantidad-id="${id}"]`);
            // Determine quantity to check (New + Existing in Cart)
            let newQty = inputCantidad ? parseFloat(inputCantidad.value) : 1;
            if (cantidad_scann != null) newQty = parseFloat(cantidad_scann);

            const idStr = id.toString();
            let currentInCart = 0;
            if (carritoActivo[idStr]) {
                currentInCart = carritoActivo[idStr].qty;
            }

            const totalQtyToCheck = newQty + currentInCart;

            const disponible = await verificarStock(id, totalQtyToCheck);
            
            if (!disponible['result']) {
                Notiflix.Notify.failure(disponible['message']);
                return;
            }

            if (btnElement) {
                const $btn = $(btnElement);
                const $cart = $('#cartDropdown');

                $btn.addClass('sendtocart');
                setTimeout(function() {
                    $btn.removeClass('sendtocart');
                    $cart.addClass('shake');
                    setTimeout(function() {
                        $cart.removeClass('shake');
                    }, 500);
                }, 1000);
            }

            let cant = inputCantidad ? parseFloat(inputCantidad.value) : 1;
            if (cantidad_scann != null) cant = parseFloat(cantidad_scann);

            if (isNaN(cant) || cant <= 0) {
                alert('Cantidad inválida. Debe ser un número mayor a 0.');
                return;
            }

            if(inputCantidad) inputCantidad.value = 1;

            if (!productos_por_id[id]) {
                console.error(`Producto con ID ${id} no encontrado.`);
                return;
            }
            const idPedido = id.toString();
            const producto = productos_por_id[idPedido];

            if (carritoActivo[idPedido]) {
                carritoActivo[idPedido].qty += cant;
            } else {
                carritoActivo[idPedido] = {
                    id: idPedido,
                    name: producto.nombre,
                    price_C: parseFloat(producto.price_C),
                    price_C_Bs: parseFloat(producto.price_C_Bs),
                    price_C_Cop: parseFloat(producto.price_C_Cop),
                    price: parseFloat(dolarventa_p),
                    pricePeso: parseFloat(pesoventa_p),
                    priceBolivar: parseFloat(bolivarventa_p),
                    qty: cant,
                    mayor: mayor,
                    cantidadPaca: 1
                };
            }

            if (carritoActivo[idPedido].qty == 0) {
                await db.carritoActivo.delete(idPedido);
            } else {
                await db.carritoActivo.put(carritoActivo[idPedido]);
            }
            await updateCartTimestamp();
            showToastCart()
            actualizarCarritoJs();
        }

        async function actualizarCarritoJs() {
            total_pesos = total_dolares = total_bolivares = 0;
            const items = Object.values(carritoActivo);

            const cartItems = $("#cart-items");
            const cartFooter = $("#cart-footer");
            const cartCount = $("#cart-count");
            const cartItemsMobile = $(".cart-items-mobile");
            const cartFooterMobile = $(".cart-footer-mobile");
            const cartCountMobile = $(".cart-count-mobile");

            if (items.length > 0) {
                let html = '';
                items.forEach(element => {
                    let subtotalBolivar = element.priceBolivar * element.qty;
                    let subtotalDolar = element.price * element.qty;

                    html += `
                        <div class="cart-item mb-3 pb-3 border-bottom">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="flex-grow-1">
                                    <h6 class="mb-1 small">${element.name}</h6>
                                    <div class="text-muted small">Cantidad: ${element.qty}</div>
                                    <div class="small">
                                        <span class="text-success">$${formatNumber(recortarADosDecimales(subtotalDolar))}</span>
                                    </div>
                                </div>
                                <button class="btn btn-sm btn-outline-danger" onclick="quitarProductoJs('${element.id}')">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </div>
                    `;

                    total_dolares += subtotalDolar;
                    total_bolivares += subtotalBolivar;
                });

                cartItems.html(html);
                cartItemsMobile.html(html);
                total_pesos = Math.round(total_pesos);

                $("#cart-total-dolar, .cart-total-dolar-mobile").text(`$${formatNumber(recortarADosDecimales(total_dolares))}`);
                $("#cart-total-bs, .cart-total-bs-mobile").text(`${formatNumber(recortarADosDecimales(total_bolivares))} Bs`);

                cartFooter.removeClass('hide');
                cartFooterMobile.removeClass('hide');
                cartCount.text(items.length);
                cartCountMobile.text(items.length);
            } else {
                cartItems.html('<p class="text-muted text-center mb-0">El carrito está vacío</p>');
                cartItemsMobile.html('<p class="text-muted text-center mb-0">El carrito está vacío</p>');
                cartFooter.addClass('hide');
                cartFooterMobile.addClass('hide');
                cartCount.text('0');
                cartCountMobile.text('0');
            }
        }

        async function quitarProductoJs(id) {
            await deleteFromIndexedDB('carritoActivo', id);
            await updateCartTimestamp();
            await actualizarCarritoActivo()
        }

        async function vaciarCarritoJs() {
            if (confirm('¿Estás seguro de que deseas vaciar el carrito?')) {
                carritoActivo = {};
                await db.carritoActivo.clear();
                await db.cart_meta.delete('last_updated');
                actualizarCarritoJs();
            }
        }

        // ======================
        // FORMATEO DE NÚMEROS
        // ======================
        function formatNumber(num) {
            return parseFloat(num).toLocaleString('es-VE', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        }

        function formatPeso(num) {
            return Math.round(parseFloat(num));
        }

        function formatearMiles(num) {
            return parseFloat(num).toLocaleString('es-VE');
        }

        function recortarADosDecimales(num) {
            return Math.floor(parseFloat(num) * 100) / 100;
        }

        // ======================
        // PROCESAR PEDIDO (VENTA)
        // ======================
        async function procesarPedido(metodoPago, despacho, nombreC = null) {
            if (!carritoActivo || Object.keys(carritoActivo).length === 0) {
                console.warn("No hay carrito activo para procesar.");
                return false;
            }

            const idPedido = String(Date.now());;
            const datosCliente = {
                nombre: nombreC || "",
                cedula: "",
                telefono: ""
            };

            let valorFinalBs = 0;
            let valorFinalCop = 0;
            let valorFinalVenta = 0;

            for (let k in carritoActivo) {
                if (carritoActivo.hasOwnProperty(k)) {
                    let prod = carritoActivo[k];
                    valorFinalVenta += prod.price * (prod.qty ?? 1);
                    valorFinalBs += prod.priceBolivar * (prod.qty ?? 1);
                    valorFinalCop += prod.pricePeso * (prod.qty ?? 1);
                }
            }

            let nuevoPedido = {
                id: idPedido,
                metodoPago,
                despacho,
                valorFinalVenta,
                valorFinalBs,
                valorFinalCop,
                datosCliente,
                productos: carritoActivo
            };

            try {
                await db.carritosVenta.put(nuevoPedido);
                carritoActivo = {};
                await db.carritoActivo.clear();
                enviarPedidosProcesados();
                return true;
            } catch (e) {
                console.error("Error guardando en IndexedDB, ", e);
            }
        }

        // ======================
        // ENVIAR PEDIDOS PROCESADOS
        // ======================
        async function enviarPedidosProcesados() {
            let pedidosIndexedDB = await db.carritosVenta.toArray();

            if (pedidosIndexedDB.length === 0) {
                console.warn("No hay pedidos procesados para enviar.");
                return;
            }

            total_dolares = 0;
            total_bolivares = 0;
            total_pesos = 0;

            fetch(base_url + 'accion_carta.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: new URLSearchParams({
                            action: 'enviarPedidos',
                            pedidos: JSON.stringify(pedidosIndexedDB),
                        }),
                    })
                    .then((res) => res.text())
                    .then(async (text) => {

                        let response;
                        try {
                            response = JSON.parse(text);
                        } catch (e) {
                            console.error('Error al parsear JSON:', e);
                            alert('Respuesta no válida del servidor.');
                            return;
                        }

                        if (response.status) {
                            alert('Venta procesada correctamente.');
                            await db.carritosVenta.clear();
                            actualizarCarritoJs();
                        } else {
                            alert(response.data || 'Error en la respuesta del servidor.');
                        }
                    })
                    .catch((error) => {
                        console.error('Error en fetch:', error);
                        alert('Error al enviar los pedidos. Intente nuevamente.');
                    });
        }

        async function deleteFromIndexedDB(storeName, id) {
            try {
                await db.table(storeName).delete(String(id));
                console.log(`Registro con ID ${id} eliminado de ${storeName}`);
            } catch (err) {
                console.error(`Error eliminando de ${storeName}`, err);
            }
        }

        async function getFromIndexedDB(storeName, id) {
            try {
                return await db.table(storeName).get(String(id));
            } catch (error) {
                console.error(`Error obteniendo registro de ${storeName}`, error);
                return null;
            }
        }

        async function getAllFromIndexedDB(storeName) {
            try {
                return await db.table(storeName).toArray();
            } catch (error) {
                console.error(`Error obteniendo todos los registros de ${storeName}`, error);
                return [];
            }
        }
    </script>

    <!-- Chat Component (Simplified) -->
    <?php include 'assets/components/chat-button.php'; ?>
    <script src="assets/js/chat-simple.js"></script>
</body>
</html>