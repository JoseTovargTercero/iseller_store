<?php
require_once 'includes/session.php';
requireAdminLogin();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Categorías - iSeller Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/admin.css">
    <link rel="stylesheet" href="../assets/dist/notiflix-Notiflix-67ba12d/dist/notiflix-3.2.8.min.css" />
    <meta name="csrf-token" content="<?php echo getCSRFToken(); ?>">
</head>
<body>
    
    <?php include 'includes/nav.php'; ?>

    <div class="container-fluid px-4 py-4">
        
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
            <div>
                <h1 class="h3 fw-bold mb-0 text-dark">Gestión de Categorías</h1>
                <p class="text-muted small mb-0">Administra las categorías de tus productos</p>
            </div>
            <button class="btn btn-primary rounded-pill px-4" id="btnNewCategory">
                <i class="bi bi-plus-lg me-2"></i>Nueva Categoría
            </button>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light text-muted small text-uppercase">
                            <tr>
                                <th class="ps-3" style="width: 100px;">ID</th>
                                <th>Nombre</th>
                                <th>Fecha Creación</th>
                                <th class="text-end pe-3">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="categories-table-body">
                            <!-- JS -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div id="loading-state" class="text-center py-5 d-none">
            <div class="spinner-border text-primary"></div>
        </div>
        
        <div id="empty-state" class="text-center py-5 d-none">
            <i class="bi bi-tags fs-1 text-muted"></i>
            <p class="text-muted">No hay categorías creadas aún.</p>
        </div>

    </div>

    <!-- Modal Category -->
    <div class="modal fade" id="modalCategory" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="modalTitle">Nueva Categoría</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <form id="formCategory">
                        <input type="hidden" id="categoryId">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Nombre de la Categoría</label>
                            <input type="text" class="form-control form-control-lg" id="categoryName" placeholder="Ej: Lácteos, Bebidas, Limpieza..." required>
                        </div>
                    </form>
                </div>
                <div class="modal-footer bg-light border-0">
                    <button type="button" class="btn btn-link link-secondary text-decoration-none" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" form="formCategory" class="btn btn-primary px-4 rounded-pill" id="btnSaveCategory">Guardar</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/dist/notiflix-Notiflix-67ba12d/dist/notiflix-3.2.8.min.js"></script>
    <script src="assets/js/categorias.js"></script>
</body>
</html>
