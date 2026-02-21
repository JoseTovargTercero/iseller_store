<?php
require_once 'includes/session.php';
requireAdminLogin();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Productos - iSeller Admin</title>
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <!-- Toastify -->
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/admin.css">
    <style>
        .product-img-thumb {
            width: 50px;
            height: 50px;
            object-fit: contain;
            background: #fff;
            border: 1px solid #eee;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    
    <?php include 'includes/nav.php'; ?>

    <div class="container-fluid px-4 py-4">
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="mb-0 fw-bold">Gestión de Productos</h4>
            <div class="input-group" style="max-width: 300px;">
                <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                <input type="text" class="form-control border-start-0" id="searchProductInput" placeholder="Buscar por nombre o código...">
            </div>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light text-muted small text-uppercase">
                            <tr>
                                <th class="ps-3">Imagen</th>
                                <th>Nombre</th>
                                <th>Código</th>
                                <th class="text-center">Stock</th>
                                <th>Categorías</th>
                                <th class="text-end">Precio (USD)</th>
                                <th class="text-end">Precio (Bs)</th>
                                <th class="text-end pe-3">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="products-table-body">
                            <!-- JS Content -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Loader -->
        <div id="products-loading" class="text-center py-5">
            <div class="spinner-border text-primary"></div>
        </div>
        <div id="products-empty" class="text-center py-5 d-none">
            <i class="bi bi-box-seam fs-1 text-muted"></i>
            <p class="text-muted">No se encontraron productos</p>
        </div>

    </div>

    <!-- Modal Associate Categories -->
    <div class="modal fade" id="modalAssociateCategories" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title"><i class="bi bi-tags-fill me-2"></i>Asociar Categorías</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <input type="hidden" id="assocProductId">
                    <p class="text-muted small mb-3">Selecciona las categorías para <strong id="assocProductName"></strong>:</p>
                    <div id="categoriesList" class="row g-2 overflow-auto" style="max-height: 300px;">
                        <!-- JS Content -->
                        <div class="col-12 text-center py-3"><div class="spinner-border text-primary spinner-border-sm"></div></div>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0">
                    <button type="button" class="btn btn-link link-secondary text-decoration-none" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary px-4 rounded-pill" id="btnSaveAssociations">Guardar Cambios</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Upload Image -->
    <div class="modal fade" id="modalUploadImage" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Actualizar Imagen</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center">
                    <input type="hidden" id="uploadProductId">
                    <div class="mb-3">
                        <img id="previewImage" src="" class="img-fluid rounded mb-3" style="max-height: 200px; display: none;">
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-start w-100 fw-bold">Seleccionar archivo (PNG/JPG)</label>
                        <input class="form-control" type="file" id="imageInput" accept="image/png, image/jpeg">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" id="btnConfirmUpload">Subir Imagen</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
    <script src="assets/js/products.js"></script>
</body>
</html>
