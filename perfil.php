<?php
require_once('core/db.php');
require_once('core/session.php');

// Verificar que el usuario esté logueado
requireLogin();

$userName = getUserName();
?>
<!DOCTYPE html>
<html lang='es'>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Perfil - iSeller Store</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <!-- Custom Styles -->
    <link rel="stylesheet" href="assets/css/global-styles.css">
    
    <style>
        body {
            background-color: #f8f9fa;
        }
        
        .profile-header {
            background: #6faf7a;
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        .profile-avatar {
            width: 100px;
            height: 100px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            color: rgb(111, 175, 122);
            font-weight: bold;
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
        
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            transition: transform 0.2s, box-shadow 0.2s;
            border: 1px solid #e5e7eb;
        }
        
        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.12);
        }
        
        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }
        
        .stat-icon.green {
            background: #ECFDF5;
            color: rgb(111, 175, 122);
        }
        
        .stat-icon.blue {
            background: #EFF6FF;
            color: #3B82F6;
        }
        
        .stat-icon.yellow {
            background: #FEF3C7;
            color: #F59E0B;
        }
        
        .nav-tabs .nav-link {
            color: #6b7280;
            border: none;
            border-bottom: 3px solid transparent;
            font-weight: 500;
            padding: 1rem 1.5rem;
        }
        
        .nav-tabs .nav-link:hover {
            border-bottom-color: #d1d5db;
            color: #374151;
        }
        
        .nav-tabs .nav-link.active {
            color: rgb(111, 175, 122);
            border-bottom-color: rgb(111, 175, 122);
            background: none;
        }
        
        .progress-bar-custom {
            height: 12px;
            background-color: #e5e7eb;
            border-radius: 99px;
            overflow: hidden;
            box-shadow: inset 0 1px 2px rgba(0,0,0,0.05);
        }
        
        .progress-fill-custom {
            height: 100%;
            background: linear-gradient(90deg, rgb(111, 175, 122), #34d399);
            border-radius: 99px;
            transition: width 1.5s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .reward-card {
            background: white;
            border-radius: 12px;
            padding: 1.25rem;
            margin-bottom: 1rem;
            border: 2px solid #e5e7eb;
            transition: all 0.2s;
        }
        
        .reward-card.disponible {
            border-color: rgb(111, 175, 122);
            background: linear-gradient(to right, #f0fdf4, white);
        }
        
        .reward-card.usada {
            opacity: 0.6;
        }
        
        .reward-card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
    .order-card{
    border:1px solid #eee;
    border-radius:14px;
    box-shadow:0 6px 16px rgba(0,0,0,.05);
    background:#fff;
}

/* ===== Timeline ===== */

.order-timeline{
    display:flex;
    justify-content:space-between;
    position:relative;
    margin-top:10px;
}

.order-timeline::before{
    content:'';
    position:absolute;
    top:17px; /* Center of dot (14px + margin top 5) approx? No, dot has margin 0 auto 4px. Container margin top 10. */
    /* Let's check vertical alignment first */
    /* .timeline-step margin-top: 5px. .dot height 14px. */
    /* Dot top relative to order-timeline: 5px (margin) */
    /* Center of dot: 5px + 7px = 12px. */
    /* .order-timeline::before top: 10px? Close enough to 12. */
    top:11px;
    left:12.5%;
    right:12.5%;
    height:3px;
    background:#e9ecef;
    z-index:0;
}

.timeline-progress {
    position: absolute;
    top: 11px;
    left: 12.5%;
    height: 3px;
    background-color: #0d6efd;
    z-index: 0;
    transition: width 0.3s ease;
}

.timeline-step{
    position:relative;
    z-index:1;
    text-align:center;
    flex:1;
    margin-top: 5px;
}

.timeline-step .dot{
    width:14px;
    height:14px;
    background:#ced4da;
    border-radius:50%;
    display:block;
    margin:0 auto 4px;
}

.timeline-step.active .dot{
    background:#0d6efd;
}

.timeline-step small{
    font-size:.75rem;
    color:#6c757d;
}

.timeline-step.active small{
    color:#0d6efd;
    font-weight:600;
}

        .order-card.pendiente {
            border-left-color: #F59E0B;
        }
        
        .order-card.en_revision {
            border-left-color: #3B82F6;
        }
        
        .order-card.enviada {
            border-left-color: rgb(111, 175, 122);
        }
        
        .badge-status {
            padding: 0.4rem 0.8rem;
            border-radius: 99px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        
        .skeleton {
            background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
            background-size: 200% 100%;
            animation: loading 1.5s infinite;
            border-radius: 8px;
        }
        
        @keyframes loading {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }
        
        .level-badge-large {
            background: linear-gradient(135deg, rgb(111, 175, 122), #047857);
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 99px;
            font-weight: 700;
            font-size: 1.1rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }
        
        .address-card {
            background: white;
            border-radius: 12px;
            padding: 1.25rem;
            margin-bottom: 1rem;
            border: 2px solid #e5e7eb;
            transition: all 0.2s;
        }
        
        .address-card.predeterminada {
            border-color: rgb(111, 175, 122);
            background: linear-gradient(to right, #f0fdf4, white);
        }

        .address-card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
    </style>
</head>

<body>
    <!-- Navbar -->
    <nav class="navbar navbar-custom fixed-top">
        <div class="container-fluid d-flex align-items-center justify-content-between px-3 px-md-5">
            <a class="navbar-brand" href="index.php">
                <i class="bi bi-shop-window text-success"></i>
                <span style="color: var(--primary-color);">iSeller</span> <span style="color: var(--text-primary);">Store</span>
            </a>
            
            <div class="header-actions d-flex align-items-center gap-3">
                <a href="index.php" class="btn-icon" title="Inicio">
                    <i class="bi bi-house-fill"></i>
                </a>
                <a href="checkout.php" class="btn-icon" title="Checkout">
                    <i class="bi bi-cart-fill"></i>
                </a>
                <div class="dropdown">
                    <button class="btn-icon" data-bs-toggle="dropdown" title="Mi Cuenta">
                        <i class="bi bi-person"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end custom-dropdown-menu">
                        <li><h6 class="dropdown-header"><?php echo htmlspecialchars($userName); ?></h6></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item active" href="perfil.php"><i class="bi bi-person-circle me-2"></i> Mi Perfil</a></li>
                        <li><a class="dropdown-item" href="checkout.php"><i class="bi bi-cart-check me-2"></i> Checkout</a></li>
                        <li><a class="dropdown-item" href="logout.php"><i class="bi bi-box-arrow-right me-2"></i> Cerrar Sesión</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <!-- Profile Header -->
    <div class="profile-header" style="margin-top: 70px;">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-auto">
                    <div class="profile-avatar" id="profile-avatar">
                        <div class="skeleton" style="width: 100px; height: 100px; border-radius: 50%;"></div>
                    </div>
                </div>
                <div class="col">
                    <h2 class="mb-1 text-white" id="profile-name">
                        <div class="skeleton" style="width: 200px; height: 32px;"></div>
                    </h2>
                    <p class="mb-2 opacity-75" id="profile-email">
                        <div class="skeleton" style="width: 250px; height: 20px;"></div>
                    </p>
                    <div id="profile-level">
                        <div class="skeleton" style="width: 150px; height: 40px; border-radius: 99px;"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container pb-5">
        <!-- Stats Cards -->
        <div class="row g-4 mb-4">
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="stat-icon green">
                        <i class="bi bi-trophy-fill"></i>
                    </div>
                    <h3 class="mb-0 fw-bold" id="stat-puntos">
                        <div class="skeleton" style="width: 80px; height: 32px;"></div>
                    </h3>
                    <p class="text-muted mb-0 small">Puntos Acumulados</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="stat-icon blue">
                        <i class="bi bi-bag-check-fill"></i>
                    </div>
                    <h3 class="mb-0 fw-bold" id="stat-compras">
                        <div class="skeleton" style="width: 60px; height: 32px;"></div>
                    </h3>
                    <p class="text-muted mb-0 small">Compras Realizadas</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="stat-icon yellow">
                        <i class="bi bi-gift-fill"></i>
                    </div>
                    <h3 class="mb-0 fw-bold" id="stat-recompensas">
                        <div class="skeleton" style="width: 40px; height: 32px;"></div>
                    </h3>
                    <p class="text-muted mb-0 small">Recompensas Disponibles</p>
                </div>
            </div>
        </div>

        <!-- Tabs -->
        <ul class="nav nav-tabs mb-4" id="profileTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="loyalty-tab" data-bs-toggle="tab" data-bs-target="#loyalty" type="button">
                    <i class="bi bi-star-fill me-2"></i>Progreso de Puntos
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="rewards-tab" data-bs-toggle="tab" data-bs-target="#rewards" type="button">
                    <i class="bi bi-gift-fill me-2"></i>Recompensas
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="orders-tab" data-bs-toggle="tab" data-bs-target="#orders" type="button">
                    <i class="bi bi-clock-history me-2"></i>Listado de Compras
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="info-tab" data-bs-toggle="tab" data-bs-target="#info" type="button">
                    <i class="bi bi-person-fill me-2"></i>Información Personal
                </button>
            </li>
        </ul>

        <!-- Tab Content -->
        <div class="tab-content" id="profileTabContent">
            <!-- Loyalty Progress Tab -->
            <div class="tab-pane fade show active" id="loyalty" role="tabpanel">
                <div class="stat-card">
                    <h4 class="fw-bold mb-4"><i class="bi bi-graph-up-arrow me-2 text-success"></i>Tu Progreso de Fidelidad</h4>
                    
                    <div class="row align-items-center mb-4">
                        <div class="col-md-8">
                            <div class="d-flex justify-content-between mb-2">
                                <span class="fw-semibold" id="loyalty-text">Cargando...</span>
                                <span class="text-muted" id="loyalty-fraction">0 / 10</span>
                            </div>
                            <div class="progress-bar-custom">
                                <div class="progress-fill-custom" id="loyalty-progress" style="width: 0%"></div>
                            </div>
                        </div>
                        <div class="col-md-4 text-md-end mt-3 mt-md-0">
                            <div class="level-badge-large" id="loyalty-badge">
                                <i class="bi bi-star-fill"></i> Nivel 1
                            </div>
                        </div>
                    </div>
                    
                    <div class="alert alert-info border-0" style="background: #EFF6FF;">
                        <i class="bi bi-info-circle-fill me-2"></i>
                        <strong>¿Cómo funciona?</strong> Cada compra te otorga puntos basados en la ganancia generada. 
                        Acumula 10 puntos para subir de nivel. Cada nivel múltiplo de 5 desbloquea un bono de $5.
                    </div>
                </div>
            </div>

            <!-- Rewards Tab -->
            <div class="tab-pane fade" id="rewards" role="tabpanel">
                <div class="stat-card">
                    <h4 class="fw-bold mb-4"><i class="bi bi-gift-fill me-2 text-warning"></i>Mis Recompensas</h4>
                    <div id="rewards-list">
                        <!-- Loading skeleton -->
                        <div class="reward-card">
                            <div class="skeleton" style="width: 100%; height: 80px;"></div>
                        </div>
                        <div class="reward-card">
                            <div class="skeleton" style="width: 100%; height: 80px;"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Orders Tab -->
            <div class="tab-pane fade" id="orders" role="tabpanel">
                <div class="stat-card">
                    <h4 class="fw-bold mb-4"><i class="bi bi-list me-2 text-primary"></i>Listado de Compras</h4>
                    <div id="orders-list">
                        <!-- Loading skeleton -->
                        <div class="order-card">
                            <div class="skeleton" style="width: 100%; height: 100px;"></div>
                        </div>
                        <div class="order-card">
                            <div class="skeleton" style="width: 100%; height: 100px;"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Personal Info Tab -->
            <div class="tab-pane fade" id="info" role="tabpanel">
                <div class="stat-card">
                    <h4 class="fw-bold mb-4"><i class="bi bi-person-circle me-2 text-success"></i>Información Personal</h4>
                    
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Nombre</label>
                            <input type="text" class="form-control" id="info-nombre" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Email</label>
                            <input type="email" class="form-control" id="info-email" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Nivel</label>
                            <input type="text" class="form-control" id="info-nivel" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Puntos</label>
                            <input type="text" class="form-control" id="info-puntos" readonly>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Miembro desde</label>
                            <input type="text" class="form-control" id="info-fecha" readonly>
                        </div>
                    </div>
                    
                    <h5 class="fw-bold mb-3 mt-4"><i class="bi bi-geo-alt-fill me-2"></i>Direcciones Guardadas</h5>
                    <div id="addresses-list">
                        <div class="skeleton" style="width: 100%; height: 100px; margin-bottom: 1rem;"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Detalle Orden -->
    <div class="modal fade" id="modalDetalleOrden" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <div class="modal-header bg-light border-0">
                    <h5 class="modal-title fw-bold" id="modalDetalleLabel">Detalle de Orden</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <!-- Loading State -->
                    <div id="modal-loader" class="text-center py-5">
                        <div class="spinner-border text-success" role="status">
                            <span class="visually-hidden">Cargando...</span>
                        </div>
                        <p class="mt-2 text-muted">Cargando detalles...</p>
                    </div>

                    <!-- Content -->
                    <div id="modal-content" style="display: none;">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <div>
                                <h5 class="fw-bold mb-1">Orden #<span id="detail-id"></span></h5>
                                <span class="text-muted small"><i class="bi bi-calendar3 me-1"></i> <span id="detail-date"></span></span>
                            </div>
                        </div>

                        <!-- Info Entrega -->
                        <div class="card bg-light border-0 rounded-3 mb-4">
                            <div class="card-body">
                                <h6 class="fw-bold mb-3"><i class="bi bi-truck me-2 text-success"></i>Información de Entrega</h6>
                                <p class="mb-1"><strong>Tipo:</strong> <span id="detail-delivery-type"></span></p>
                                <p class="mb-1"><strong>Dirección:</strong> <span id="detail-address"></span></p>
                                <p class="mb-0"><strong>Ref. Pago:</strong> <span id="detail-payment-ref"></span></p>
                            </div>
                        </div>

                        <!-- Productos -->
                        <h6 class="fw-bold mb-3">Productos</h6>
                        <div class="table-responsive mb-4">
                            <table class="table align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Producto</th>
                                        <th class="text-center">Cant.</th>
                                        <th class="text-end">Precio</th>
                                        <th class="text-end">Total</th>
                                    </tr>
                                </thead>
                                <tbody id="detail-items-list">
                                    <!-- Items loop -->
                                </tbody>
                            </table>
                        </div>

                        <!-- Totales -->
                        <div class="row justify-content-end">
                            <div class="col-md-6">
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-muted">Subtotal</span>
                                    <span class="fw-semibold">$<span id="detail-subtotal">0.00</span></span>
                                </div>
                                <div class="d-flex justify-content-between mb-2 text-success">
                                    <span>Descuento</span>
                                    <span class="fw-semibold">-$<span id="detail-discount">0.00</span></span>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-muted">Envío</span>
                                    <span class="fw-semibold">$<span id="detail-shipping">0.00</span></span>
                                </div>
                                <hr>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="fs-5 fw-bold text-dark">Total</span>
                                    <span class="fs-4 fw-bold text-success">$<span id="detail-total">0.00</span></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Error State -->
                     <div id="modal-error" class="text-center py-5 d-none">
                        <i class="bi bi-exclamation-circle text-danger fs-1"></i>
                        <p class="mt-2 text-danger">Error al cargar la orden</p>
                    </div>

                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Cerrar</button>
                   
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Fetch profile data
        async function loadProfileData() {
            try {
                const res = await fetch('api/perfil_data.php');
                const data = await res.json();

                
                if (!data.success) {
                    alert('Error al cargar datos: ' + data.message);
                    return;
                }
                
                // Update Header
                const initials = data.user.nombre.split(' ').map(n => n[0]).join('').toUpperCase().substring(0, 2);
                document.getElementById('profile-avatar').innerHTML = initials;
                document.getElementById('profile-name').textContent = data.user.nombre;
                document.getElementById('profile-email').textContent = data.user.email;
                document.getElementById('profile-level').innerHTML = `
                    <span class="level-badge-large">
                        <i class="bi bi-star-fill"></i> Nivel ${data.user.nivel}
                    </span>
                `;
                
                // Update Stats
                document.getElementById('stat-puntos').textContent = data.user.puntos.toFixed(2);
                document.getElementById('stat-compras').textContent = data.stats.total_compras;
                const rewardsDisponibles = data.rewards.filter(r => r.estado === 'disponible').length;
                document.getElementById('stat-recompensas').textContent = rewardsDisponibles;
                
                // Update Loyalty Progress
                const falta = data.user.falta;
                document.getElementById('loyalty-text').innerHTML = falta <= 0 
                    ? '¡Felicidades! Has completado este nivel' 
                    : `Faltan <strong>${falta.toFixed(2)} puntos</strong> para tu próxima recompensa`;
                document.getElementById('loyalty-fraction').textContent = `${data.user.progreso.toFixed(2)} / 10`;
                document.getElementById('loyalty-badge').innerHTML = `<i class="bi bi-star-fill"></i> Nivel ${data.user.nivel}`;
                
                setTimeout(() => {
                    document.getElementById('loyalty-progress').style.width = data.user.porcentaje + '%';
                }, 300);
                
                // Update Rewards
                renderRewards(data.rewards);
                // Update Orders
                renderOrders(data.orders);
                
                // Update Personal Info
                document.getElementById('info-nombre').value = data.user.nombre;
                document.getElementById('info-email').value = data.user.email;
                document.getElementById('info-nivel').value = 'Nivel ' + data.user.nivel;
                document.getElementById('info-puntos').value = data.user.puntos.toFixed(2) + ' puntos';
                document.getElementById('info-fecha').value = new Date(data.user.created_at).toLocaleDateString('es-ES');
                
                // Update Addresses
                renderAddresses(data.addresses);
                
            } catch (err) {
                console.error('ERROR COMPLETO:', err.message || err);
            }
        }
        
        function renderRewards(rewards) {
            const container = document.getElementById('rewards-list');
            
            if (rewards.length === 0) {
                container.innerHTML = `
                    <div class="text-center py-5 text-muted">
                        <i class="bi bi-gift" style="font-size: 3rem;"></i>
                        <p class="mt-3">No tienes recompensas aún. ¡Sigue comprando para desbloquearlas!</p>
                    </div>
                `;
                return;
            }
            
            container.innerHTML = rewards.map(r => {
                let statusBadge = '';
                if (r.estado === 'disponible') {
                    statusBadge = '<span class="badge bg-success">Disponible</span>';
                } else if (r.estado === 'bloqueado') {
                    statusBadge = '<span class="badge bg-info">Pendiente por desbloquear</span>';
                } else if (r.estado === 'usado') {
                    statusBadge = '<span class="badge bg-secondary">Usada</span>';
                }


                
                const icon = r.tipo === 'monetaria' 
                    ? '<i class="bi bi-cash-coin text-warning" style="font-size: 2rem;"></i>'
                    : '<i class="bi bi-percent text-info" style="font-size: 2rem;"></i>';
                
                const title = r.tipo === 'monetaria'
                    ? `Bono de $${parseFloat(r.monto).toFixed(2)}`
                    : 'Descuento Especial';
                
                const description = `Desbloqueado al alcanzar Nivel ${r.nivel_desbloqueo}`;
                
                return `
                    <div class="reward-card ${r.estado}">
                        <div class="d-flex align-items-center gap-3">
                            <div>${icon}</div>
                            <div class="flex-grow-1">
                                <h6 class="mb-1 fw-bold">${title}</h6>
                                <p class="mb-0 small text-muted">${description}</p>
                            </div>
                            <div>${statusBadge}</div>
                        </div>
                    </div>
                `;
            }).join('');
        }
        
        function renderOrders(orders) {
    const container = document.getElementById('orders-list');

    if (orders.length === 0) {
        container.innerHTML = `
            <div class="text-center py-5 text-muted">
                <i class="bi bi-check-circle" style="font-size:3rem;"></i>
                <p class="mt-3">No tienes compras pendientes. ¡Todas tus órdenes han sido entregadas!</p>
            </div>
        `;
        return;
    }

    const statusMap = [
        { key: 'pendiente', label: 'Pendiente' },
        { key: 'en_revision', label: 'En revisión' },
        { key: 'enviada', label: 'Enviada' },
        { key: 'entregada', label: 'Entregada' }
    ];

    const statusColors = {
        'pendiente': 'warning',
        'en_revision': 'primary',
        'enviada': 'success',
        'entregada': 'success',
        'rechazada': 'danger'
    };

    container.innerHTML = orders.map(o => {

        const totalBase = parseFloat(o.valor_compra) + parseFloat(o.importe_envio || 0);
        let totalFinal = totalBase;
        let descuentoHTML = '';
        let totalOldHTML = '';

        if (parseFloat(o.ahorrado) > 0) {
            totalFinal = totalBase - parseFloat(o.ahorrado);
            totalOldHTML = `<div class="text-muted small">Antes: $${totalBase.toFixed(2)}</div>`;
            descuentoHTML = `<span class="badge bg-success mt-1"><i class="bi bi-gift"></i> Descuento: $${parseFloat(o.ahorrado).toFixed(2)}</span>`;
        }

        const currentIndex = statusMap.findIndex(s => s.key === o.estado);
        const progressWidth = currentIndex >= 0 ? (currentIndex * 25) + '%' : '0%';
        const opacity = o.estado === 'entregada' ? 'opacity: 0.5;' : '';

        const timelineHTML = `
            <div class="order-timeline mb-3">
                <div class="timeline-progress" style="width: ${progressWidth}"></div>
                ${statusMap.map((s, i) => `
                    <div class="timeline-step ${i <= currentIndex ? 'active' : ''}">
                        <span class="dot"></span>
                        <small>${s.label}</small>
                    </div>
                `).join('')}
            </div>
        `;

        return `
        <div class="order-card mb-4 p-4" style="${opacity}">

            <!-- HEADER -->
            <div class="d-flex justify-content-between align-items-start mb-2">
                <div>
                    <h6 class="fw-bold mb-1">Pedido #${o.id}</h6>
                    <div class="small text-muted">
                        <i class="bi bi-calendar3 me-1"></i>
                        ${new Date(o.fecha).toLocaleDateString('es-ES')}
                    </div>
                </div>

                <span class="badge rounded-pill bg-${statusColors[o.estado]} px-3 py-2">
                    ${statusMap.find(s => s.key === o.estado)?.label || o.estado}
                </span>
            </div>
            <!-- BODY -->
            <div class="row align-items-center">
                <!-- INFO -->
                <div class="col-lg-6 col-md-6 align-self-start">
                    <div class="fs-3 fw-bold">$${totalFinal.toFixed(2)}</div>
                        ${totalOldHTML}
                        ${descuentoHTML}
                    </div>
                <!-- ACTIONS -->
                <div class="col-lg-6 col-md-6 mt-3 mt-md-0 d-flex flex-md-column gap-2 justify-content-md-end">
                    <div class="row">
                    <div class="col-lg-12">
                    <!-- TIMELINE -->
            ${timelineHTML}
            </div>
                        <div class="col-md-6">
                          
                        
                    <div class="d-flex flex-column gap-3 small mb-3">

                        <div>
                            <i class="bi ${o.tipo_entrega === 'delivery' ? 'bi-truck' : 'bi-shop'} me-1"></i>
                            <strong>Entrega:</strong> ${o.tipo_entrega === 'delivery' ? 'Delivery' : 'Retiro'}
                        </div>

                        <div class="text-success">
                            ⭐ <strong>${parseFloat(o.puntos_generados).toFixed(2)} pts</strong>
                        </div>

                    </div>



                        </div>
                        <div class="col-lg-6 d-flex flex-column gap-2">
                        <button class="btn btn-primary w-100" onclick="verDetalleOrden(${o.id})">
                        <i class="bi bi-search"></i> Ver detalle
                    </button>
                    ${o.estado !== 'entregada' ? `
                            <a class="btn btn-success rounded px-4 w-100" target="_blank" href="https://wa.me/584160679095?text=Necesito ayuda con mi compra N° ${o.id}. Detalles: ">
                                <i class="bi bi-whatsapp me-2"></i> Soporte
                            </a>
                        ` : ''}
                        </div>
                    </div>

                </div>

            </div>
        </div>
        `;
    }).join('');
}

        
        function renderAddresses(addresses) {
            const container = document.getElementById('addresses-list');
            
            if (addresses.length === 0) {
                container.innerHTML = `
                    <div class="text-center py-4 text-muted">
                        <i class="bi bi-geo-alt" style="font-size: 2rem;"></i>
                        <p class="mt-2 mb-0">No tienes direcciones guardadas</p>
                    </div>
                `;
                return;
            }
            
            container.innerHTML = addresses.map(a => {
                const badge = a.predeterminada == 1 
                    ? '<span class="badge bg-success">Predeterminada</span>'
                    : '';
                
                return `
                    <div class="address-card ${a.predeterminada == 1 ? 'predeterminada' : ''}">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="flex-grow-1">
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <i class="bi bi-geo-alt-fill text-success"></i>
                                    <h6 class="mb-0 fw-semibold">Dirección ${a.id}</h6>
                                    ${badge}
                                </div>
                                <p class="mb-1">${a.direccion}</p>
                                ${a.referencia ? `<p class="mb-0 small text-muted"><i class="bi bi-info-circle me-1"></i>${a.referencia}</p>` : ''}
                            </div>
                        </div>
                    </div>
                `;
            }).join('');
        }
        
        async function verDetalleOrden(id) {
            const modal = new bootstrap.Modal(document.getElementById('modalDetalleOrden'));
            modal.show();

            // Reset States
            document.getElementById('modal-loader').classList.remove('d-none');
            document.getElementById('modal-content').style.display = 'none';
            document.getElementById('modal-error').classList.add('d-none');

            try {
                const res = await fetch(`api/orden_detalle.php?id=${id}`);
                const data = await res.json();

                if (!data.success) throw new Error(data.message);

                const o = data.orden;

                // Fill Header
                document.getElementById('detail-id').textContent = o.id;
                document.getElementById('detail-date').textContent = new Date(o.fecha).toLocaleDateString();
                
                // Fill Info
                document.getElementById('detail-delivery-type').textContent = o.tipo_entrega === 'delivery' ? 'Delivery' : 'Retiro en Tienda';
                document.getElementById('detail-address').textContent = o.direccion;
                document.getElementById('detail-payment-ref').textContent = o.pago_ref || 'N/A';

                // Fill Items
                const list = document.getElementById('detail-items-list');
                list.innerHTML = data.items.map(item => `
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-3">
                                <img src="assets/img/stock/${item.id}.png" class="rounded" width="50" height="50" style="object-fit: cover;" onerror="this.src='assets/img/no-image.png'"> 
                                <div>
                                    <h6 class="mb-0 small fw-bold">${item.nombre}</h6>
                                </div>
                            </div>
                        </td>
                        <td class="text-center">${item.cantidad}</td>
                        <td class="text-end">$${item.precio.toFixed(2)}</td>
                        <td class="text-end fw-bold">$${item.subtotal.toFixed(2)}</td>
                    </tr>
                `).join('');

                // Fill Totals
                document.getElementById('detail-subtotal').textContent = o.subtotal.toFixed(2);
                document.getElementById('detail-discount').textContent = o.descuento.toFixed(2);
                document.getElementById('detail-shipping').textContent = o.envio.toFixed(2);
                document.getElementById('detail-total').textContent = o.total.toFixed(2);

                // Show Content
                document.getElementById('modal-loader').classList.add('d-none');
                document.getElementById('modal-content').style.display = 'block';

            } catch (error) {
                console.error(error);
                document.getElementById('modal-loader').classList.add('d-none');
                document.getElementById('modal-error').classList.remove('d-none');
            }
        }

     
        // Load data on page load
        document.addEventListener('DOMContentLoaded', loadProfileData);
    </script>
</body>
</html>
