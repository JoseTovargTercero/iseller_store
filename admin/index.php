<?php
require_once 'includes/session.php';
requireAdminLogin();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - iSeller</title>
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <!-- Toastify -->
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/admin.css">
</head>
<body>
    
    <?php include 'includes/nav.php'; ?>

    <!-- Main Content -->
    <div class="container-fluid px-4 py-4">
        
        <!-- Stats Row -->
        <div class="row g-3 mb-4" id="stats-container">
            <!-- Loaded via JS -->
            <div class="col-12 text-center py-4"><div class="spinner-border text-primary"></div></div>
        </div>

        <!-- Toolbar & Filters -->
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <div class="btn-group" role="group" id="status-filters">
                <input type="radio" class="btn-check" name="statusFilter" id="filter-all" value="" checked>
                <label class="btn btn-outline-secondary" for="filter-all">Activas</label>

                <input type="radio" class="btn-check" name="statusFilter" id="filter-pendiente" value="pendiente">
                <label class="btn btn-outline-warning" for="filter-pendiente">Pendientes</label>

                <input type="radio" class="btn-check" name="statusFilter" id="filter-revision" value="en_revision">
                <label class="btn btn-outline-info" for="filter-revision">En Revisión</label>

                <input type="radio" class="btn-check" name="statusFilter" id="filter-enviada" value="enviada">
                <label class="btn btn-outline-primary" for="filter-enviada">Enviadas</label>

                <input type="radio" class="btn-check" name="statusFilter" id="filter-entregada" value="entregada">
                <label class="btn btn-outline-success" for="filter-entregada">Entregadas</label>

                <input type="radio" class="btn-check" name="statusFilter" id="filter-rechazada" value="rechazada">
                <label class="btn btn-outline-danger" for="filter-rechazada">Rechazadas</label>
            </div>

            <div class="input-group" style="max-width: 300px;">
                <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                <input type="text" class="form-control border-start-0" id="searchInput" placeholder="Buscar cliente o ID...">
            </div>
        </div>

        <!-- Orders Grid/List -->
        <!-- Orders Table -->
        <div class="card shadow-sm border-0">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light text-muted small text-uppercase">
                            <tr>
                                <th class="ps-3">ID</th>
                                <th>Cliente</th>
                                <th>Fecha</th>
                                <th>Entrega</th>
                                <th>Envío</th>
                                <th>Compra</th>
                                <th>Total</th>
                                <th>Ahorro</th>
                                <th>Transferido</th>
                                <th>Operación</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="orders-table-body">
                            <!-- Content loaded via JS -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- Loader / Empty State Container -->
        <div id="orders-loading" class="text-center py-5 d-none">
            <div class="spinner-border text-primary"></div>
        </div>
        <div id="orders-empty" class="text-center py-5 d-none">
            <i class="bi bi-inbox fs-1 text-muted"></i>
            <p class="text-muted">No hay órdenes encontradas</p>
        </div>

    </div>

    <!-- Modal Confirm Delivery -->
    <div class="modal fade" id="modalConfirmDelivery" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title"><i class="bi bi-check-circle-fill"></i> Confirmar Entrega</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center py-4">
                    <p class="fs-5">¿Confirmas que esta orden fue <strong>entregada</strong> al cliente?</p>
                    <p class="text-muted small">Esta acción marcará la orden como finalizada.</p>
                </div>
                <div class="modal-footer justify-content-center">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-success px-4" id="btnConfirmDeliveryAction">Sí, confirmar entrega</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Reject -->
    <div class="modal fade" id="modalReject" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title"><i class="bi bi-x-circle-fill"></i> Rechazar Orden</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Razón del rechazo (Obligatorio)</label>
                        <textarea class="form-control" id="razonRechazo" rows="3" placeholder="Ej: Pago no recibido, Sin stock..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-danger" id="btnConfirmRejectAction">Rechazar Orden</button>
                </div>
            </div>
        </div>
    </div>

     <!-- Modal Order Details -->
     <div class="modal fade" id="modalOrderDetails" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Detalle de Orden #<span id="detailOrderId"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <h6 class="text-muted text-uppercase small fw-bold mb-3">Información del Cliente</h6>
                            <div class="px-2">
                                <p class="mb-1 fw-bold" id="detailClientName"></p>
                                <p class="mb-1 text-muted small"><i class="bi bi-envelope me-2"></i><span id="detailClientEmail"></span></p>
                                <p class="mb-0 text-muted small"><i class="bi bi-telephone me-2"></i><span id="detailClientPhone"></span></p>
                            </div>

                            <h6 class="text-muted text-uppercase small fw-bold mb-3 mt-3">Operación Bancaria</h6>
                            <div class="px-2">
                                <p class="mb-1  small">Numero de Operación: <span class="fw-bold" id="detailOperacionNumero"></span></p>
                                <p class="mb-1  small">Hora de Operación: <span class="text-muted small"><i class="bi bi-clock me-2"></i><span id="detailOperacionHora"></span></span></p>
                            </div>

                            <hr>

                            <h6 class="text-muted text-uppercase small fw-bold mb-3">Productos</h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-borderless bg-light rounded">
                            <thead class="text-muted border-bottom">
                                <tr>
                                    <th>Producto</th>
                                    <th class="text-center">Cant.</th>
                                    <th class="text-end">Precio</th>
                                    <th class="text-end">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody id="detailItemsTable">
                                <!-- JS -->
                            </tbody>
                            <tfoot class="border-top">
                                <tr>
                                    <td colspan="4" class="text-end fw-bold text-success fs-5" id="detailTotal"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <h6 class="text-muted text-uppercase small fw-bold mb-3">Detalles de Entrega</h6>
                            <div class="bg-light p-3 rounded" id="detailDeliveryContainer">
                                <div class="mb-2">
                                    <span class="badge bg-secondary mb-2" id="detailDeliveryType"></span>
                                </div>
                                <div id="detailDeliveryContent" class="d-none">
                                    <p class="mb-1 small"><strong>Recibe:</strong> <span id="detailReceptor"></span></p>
                                    <p class="mb-1 small"><strong>Teléfono:</strong> <span id="detailReceptorPhone"></span></p>
                                    <p class="mb-1 small"><strong>Dirección:</strong> <br><span id="detailAddress" class="text-break"></span></p>
                                    <p class="mb-0 small fst-italic text-muted"><i class="bi bi-info-circle me-1"></i>Ref: <span id="detailRef"></span></p>
                                    
                                    <!-- Map Container -->
                                    <div id="deliveryMap" class="mt-3 rounded border" style="height: 200px; width: 100%;"></div>
                                </div>
                                <div id="detailPickupContent" class="d-none">
                                    <p class="mb-0 small text-muted"><i class="bi bi-shop me-2"></i>El cliente retirará el producto en la tienda.</p>
                                </div>
                            </div>
                        </div>
                    </div>
       
                    
                </div>
                <div class="modal-footer bg-light" id="detailModalFooter">
                    <!-- Dynamic Buttons -->
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <script src="assets/js/app.js"></script>
</body>
</html>
