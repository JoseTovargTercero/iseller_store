<?php
require_once 'includes/session.php';
requireAdminLogin();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Clientes - iSeller Admin</title>
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/admin.css">
    <!-- Notiflix -->
    <link rel="stylesheet" href="../assets/dist/notiflix-Notiflix-67ba12d/dist/notiflix-3.2.7.min.css" />
    <meta name="csrf-token" content="<?php echo getCSRFToken(); ?>">
</head>
<body>
    
    <?php include 'includes/nav.php'; ?>

    <div class="container-fluid px-4 py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 fw-bold mb-0">Gestión de Clientes</h1>
            <div class="input-group" style="max-width: 400px;">
                <span class="input-group-text bg-white border-end-0"><i class="bi bi-search"></i></span>
                <input type="text" class="form-control border-start-0 ps-0" id="searchCustomer" placeholder="Buscar por nombre, email o teléfono...">
            </div>
        </div>

        <!-- Estadísticas Rápidas -->
        <div class="row g-4 mb-4">
            <div class="col-md-4">
                <div class="card border-0 shadow-sm p-4">
                    <div class="d-flex align-items-center">
                        <div class="bg-primary bg-opacity-10 text-primary p-3 rounded-3 me-3">
                            <i class="bi bi-person-plus-fill fs-4"></i>
                        </div>
                        <div>
                            <h6 class="text-muted mb-1">Nuevos Hoy</h6>
                            <h3 class="fw-bold mb-0" id="statsToday">0</h3>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm p-4">
                    <div class="d-flex align-items-center">
                        <div class="bg-success bg-opacity-10 text-success p-3 rounded-3 me-3">
                            <i class="bi bi-people-fill fs-4"></i>
                        </div>
                        <div>
                            <h6 class="text-muted mb-1">Esta Semana</h6>
                            <h3 class="fw-bold mb-0" id="statsWeek">0</h3>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm p-4">
                    <div class="d-flex align-items-center">
                        <div class="bg-info bg-opacity-10 text-info p-3 rounded-3 me-3">
                            <i class="bi bi-calendar-check-fill fs-4"></i>
                        </div>
                        <div>
                            <h6 class="text-muted mb-1">Este Mes</h6>
                            <h3 class="fw-bold mb-0" id="statsMonth">0</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm overflow-hidden">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="px-4 py-3">Cliente</th>
                            <th class="py-3">Contacto</th>
                            <th class="py-3 text-center">Nivel</th>
                            <th class="py-3 text-center">Estado</th>
                            <th class="py-3 text-end px-4">Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="customersTable">
                        <!-- JS Loaded -->
                    </tbody>
                </table>
            </div>
            <div class="card-footer bg-white border-0 py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="text-muted small" id="paginationInfo">Mostrando 0 de 0 clientes</div>
                    <nav>
                        <ul class="pagination pagination-sm mb-0" id="paginationControls">
                            <!-- JS Loaded -->
                        </ul>
                    </nav>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Customer Details -->
    <div class="modal fade" id="modalCustomerDetails" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header border-bottom-0 pb-0">
                    <h5 class="modal-title fw-bold">Detalle del Cliente</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div id="customerDetailsContent">
                        <!-- JS Loaded -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Notiflix JS -->
    <script src="../assets/dist/notiflix-Notiflix-67ba12d/dist/notiflix-3.2.7.min.js"></script>
    <!-- Add this to load common logout handling if not in a separate js -->
    <script src="assets/js/app.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            loadCustomers();
            loadStats();
            
            let searchTimer;
            document.getElementById('searchCustomer').oninput = (e) => {
                clearTimeout(searchTimer);
                searchTimer = setTimeout(() => {
                    currentPage = 1;
                    loadCustomers(e.target.value);
                }, 400);
            };
        });

        let currentPage = 1;
        const itemsPerPage = 10;

        async function loadStats() {
            try {
                const res = await fetch('api/get_customer_stats.php');
                const data = await res.json();
                if (data.success) {
                    document.getElementById('statsToday').textContent = data.stats.today;
                    document.getElementById('statsWeek').textContent = data.stats.week;
                    document.getElementById('statsMonth').textContent = data.stats.month;
                }
            } catch (e) {
                console.error('Error loading stats:', e);
            }
        }

        async function loadCustomers(search = '') {
            const table = document.getElementById('customersTable');
            table.innerHTML = '<tr><td colspan="5" class="text-center py-5"><div class="spinner-border text-primary"></div></td></tr>';

            try {
                const res = await fetch(`api/get_customers.php?search=${encodeURIComponent(search)}&page=${currentPage}&limit=${itemsPerPage}`);
                const data = await res.json();

                if (data.success) {
                    renderCustomers(data.customers);
                    renderPagination(data.pagination);
                }
            } catch (e) {
                table.innerHTML = '<tr><td colspan="5" class="text-center py-5 text-danger">Error al cargar clientes</td></tr>';
            }
        }

        function renderPagination(pagination) {
            const controls = document.getElementById('paginationControls');
            const info = document.getElementById('paginationInfo');
            
            const start = pagination.total === 0 ? 0 : (pagination.page - 1) * pagination.limit + 1;
            const end = Math.min(pagination.page * pagination.limit, pagination.total);
            info.textContent = `Mostrando ${start} a ${end} de ${pagination.total} clientes`;

            let html = '';
            
            // Prev
            html += `
                <li class="page-item ${pagination.page === 1 ? 'disabled' : ''}">
                    <a class="page-link" href="#" onclick="changePage(${pagination.page - 1})"><i class="bi bi-chevron-left"></i></a>
                </li>
            `;

            // Pages (simple)
            for (let i = 1; i <= pagination.pages; i++) {
                if (i === 1 || i === pagination.pages || (i >= pagination.page - 2 && i <= pagination.page + 2)) {
                    html += `
                        <li class="page-item ${pagination.page === i ? 'active' : ''}">
                            <a class="page-link" href="#" onclick="changePage(${i})">${i}</a>
                        </li>
                    `;
                } else if (i === pagination.page - 3 || i === pagination.page + 3) {
                    html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
                }
            }

            // Next
            html += `
                <li class="page-item ${pagination.page === pagination.pages ? 'disabled' : ''}">
                    <a class="page-link" href="#" onclick="changePage(${pagination.page + 1})"><i class="bi bi-chevron-right"></i></a>
                </li>
            `;

            controls.innerHTML = html;
        }

        function changePage(page) {
            if (page < 1) return;
            currentPage = page;
            loadCustomers(document.getElementById('searchCustomer').value);
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        function renderCustomers(customers) {
            const table = document.getElementById('customersTable');
            
            if (customers.length === 0) {
                table.innerHTML = '<tr><td colspan="5" class="text-center py-5 text-muted">No se encontraron clientes</td></tr>';
                return;
            }

            table.innerHTML = customers.map(c => `
                <tr>
                    <td class="px-4">
                        <div class="fw-bold text-dark">${c.nombre || 'Sin nombre'}</div>
                        <div class="text-muted small">${c.email}</div>
                    </td>
                    <td>
                        <div class="small"><i class="bi bi-telephone text-muted me-1"></i> ${c.telefono || 'N/A'}</div>
                        <div class="extra-small text-muted"><i class="bi bi-calendar-plus me-1"></i> ${new Date(c.creado_en).toLocaleDateString()}</div>
                    </td>
                    <td class="text-center">
                        <span class="badge rounded-pill bg-primary bg-opacity-10 text-primary px-3">Nivel ${c.nivel}</span>
                    </td>
                    <td class="text-center">
                        ${c.estado === 1 
                            ? '<span class="badge bg-success bg-opacity-10 text-success px-3">Activo</span>' 
                            : '<span class="badge bg-danger bg-opacity-10 text-danger px-3">Baneado</span>'}
                    </td>
                    <td class="text-end px-4">
                        <button class="btn btn-light btn-sm rounded-circle me-1" onclick="viewDetails(${c.id})" title="Detalles">
                            <i class="bi bi-eye"></i>
                        </button>
                        ${c.estado === 1 
                            ? `<button class="btn btn-outline-danger btn-sm rounded-pill px-3" onclick="updateStatus(${c.id}, 0)">
                                <i class="bi bi-slash-circle me-1"></i> Banear
                               </button>`
                            : `<button class="btn btn-success btn-sm rounded-pill px-3" onclick="updateStatus(${c.id}, 1)">
                                <i class="bi bi-check-circle me-1"></i> Activar
                               </button>`
                        }
                    </td>
                </tr>
            `).join('');
        }

        async function updateStatus(id, newStatus) {
            const action = newStatus === 0 ? 'banear' : 'activar';
            
            Notiflix.Confirm.show(
                'Confirmar Acción',
                `¿Estás seguro de que deseas ${action} a este cliente?`,
                'Sí, continuar',
                'Cancelar',
                async () => {
                    try {
                        const res = await fetch('api/update_customer_status.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({ id, estado: newStatus })
                        });
                        const data = await res.json();
                        if (data.success) {
                            Notiflix.Notify.success('Estado actualizado correctamente');
                            loadCustomers(document.getElementById('searchCustomer').value);
                        } else {
                            Notiflix.Notify.failure(data.message || 'Error al actualizar estado');
                        }
                    } catch (e) {
                        Notiflix.Notify.failure('Error de conexión al servidor');
                    }
                }
            );
        }

        async function viewDetails(id) {
            const modal = new bootstrap.Modal(document.getElementById('modalCustomerDetails'));
            const content = document.getElementById('customerDetailsContent');
            content.innerHTML = '<div class="text-center py-5"><div class="spinner-border text-primary"></div></div>';
            modal.show();

            try {
                const res = await fetch(`api/get_customer_details.php?id=${id}`);
                const data = await res.json();

                if (data.success) {
                    const c = data.customer;
                    const fmt = (num) => new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(num);

                    content.innerHTML = `
                        <div class="row g-4">
                            <div class="col-md-6">
                                <h6 class="text-muted fw-bold text-uppercase small mb-3">Información General</h6>
                                <p class="mb-1"><strong>Nombre:</strong> ${c.nombre || 'No registrado'}</p>
                                <p class="mb-1"><strong>Email:</strong> ${c.email}</p>
                                <p class="mb-1"><strong>Teléfono:</strong> ${c.telefono || 'No registrado'}</p>
                                <p class="mb-1"><strong>Fecha Registro:</strong> ${new Date(c.creado_en).toLocaleString()}</p>
                            </div>
                            <div class="col-md-6">
                                <h6 class="text-muted fw-bold text-uppercase small mb-3">Estadísticas de Compra</h6>
                                <div class="bg-light p-3 rounded">
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>Pedidos Entregados:</span>
                                        <span class="fw-bold">${c.total_orders}</span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>Inversión Total:</span>
                                        <span class="fw-bold text-success">${fmt(c.total_spent)}</span>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <span>Puntos Disponibles:</span>
                                        <span class="fw-bold text-primary">${c.puntos}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 mt-4">
                                <h6 class="text-muted fw-bold text-uppercase small mb-3">Últimas 5 Órdenes</h6>
                                <div class="table-responsive">
                                    <table class="table table-sm table-borderless">
                                        <thead class="text-muted border-bottom">
                                            <tr>
                                                <th>ID</th>
                                                <th>Fecha</th>
                                                <th class="text-end">Total</th>
                                                <th class="text-center">Estado</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            ${c.last_orders.map(o => `
                                                <tr>
                                                    <td>#${o.id}</td>
                                                    <td>${new Date(o.fecha).toLocaleDateString()}</td>
                                                    <td class="text-end fw-bold">${fmt(o.total)}</td>
                                                    <td class="text-center">
                                                        <span class="badge small bg-secondary bg-opacity-10 text-secondary">${o.estado}</span>
                                                    </td>
                                                </tr>
                                            `).join('') || '<tr><td colspan="4" class="text-center py-2 text-muted">No hay órdenes registradas</td></tr>'}
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    `;
                }
            } catch (e) {
                content.innerHTML = '<div class="alert alert-danger">Error al cargar detalles</div>';
            }
        }
    </script>
</body>
</html>
