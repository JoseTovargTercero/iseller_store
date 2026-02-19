// admin/assets/js/app.js

document.addEventListener('DOMContentLoaded', () => {
    loadStats();
    loadOrders();
    setupListeners();
});

let currentOrders = [];
let pendingStatusChange = null; // {id, newStatus}
let pendingRejectionId = null;

// Config
const STATUS_FLOW = {
    'pendiente': { next: 'en_revision', prev: null, label: 'Pendiente', class: 'bg-pendiente' },
    'en_revision': { next: 'enviada', prev: 'pendiente', label: 'En Revisión', class: 'bg-revision' },
    'enviada': { next: 'entregada', prev: 'en_revision', label: 'Enviada', class: 'bg-enviada' },
    'entregada': { next: null, prev: 'enviada', label: 'Entregada', class: 'bg-entregada' },
    'rechazada': { next: null, prev: null, label: 'Rechazada', class: 'bg-rechazada' }
};

let dashboardStats = null;

function setupListeners() {
    // Logout
    const btnLogout = document.getElementById('btnLogout');
    if (btnLogout) {
        btnLogout.addEventListener('click', async () => {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
            try {
                const res = await fetch('api/auth.php', { 
                    method: 'POST', 
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'logout', csrf_token: csrfToken }) 
                });
                const data = await res.json();
                if (data.success) {
                    window.location.href = 'login.php';
                }
            } catch (e) {
                console.error("Logout error", e);
                window.location.href = 'login.php';
            }
        });
    }

    // Filters
    document.querySelectorAll('input[name="statusFilter"]').forEach(radio => {
        radio.addEventListener('change', () => loadOrders());
    });

    // Period Stats Selector
    document.querySelectorAll('input[name="period"]').forEach(radio => {
        radio.addEventListener('change', (e) => {
            if (dashboardStats) {
                renderStats(dashboardStats, e.target.value);
            }
        });
    });

    // Search
    let debounceTimer;
    document.getElementById('searchInput').addEventListener('input', (e) => {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => loadOrders(), 300);
    });

    // Modal Actions
    document.getElementById('btnConfirmDeliveryAction').addEventListener('click', confirmDelivery);
    document.getElementById('btnConfirmRejectAction').addEventListener('click', confirmReject);
}

async function loadStats() {
    try {
        const res = await fetch('api/dashboard_stats.php');
        const data = await res.json();
        if (data.success) {
            dashboardStats = data.stats;
            const currentPeriod = document.querySelector('input[name="period"]:checked')?.value || 'today';
            renderStats(dashboardStats, currentPeriod);
        }
    } catch (e) {
        console.error("Error loading stats", e);
    }
}

function renderStats(allStats, period) {
    const stats = allStats[period];
    const container = document.getElementById('stats-container');
    
    // Status badges class mapping
    const statusClasses = {
        'pendiente': 'warning',
        'en_revision': 'info',
        'enviada': 'primary',
        'entregada': 'success',
        'rechazada': 'danger'
    };

    container.innerHTML = `
        <!-- Main Metrics -->
        <div class="col-12 col-md-4">
            <div class="card border-0 shadow-sm h-100 overflow-hidden" style="border-left: 5px solid #007bff !important;">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="text-muted small text-uppercase fw-bold mb-1">Ingresos Estimados</p>
                            <h2 class="fw-bold mb-0 text-dark">${stats.revenue.toFixed(2)} <span class="fs-6 fw-normal">Bs</span></h2>
                        </div>
                        <div class="bg-primary bg-opacity-10 p-2 rounded">
                            <i class="bi bi-wallet2 text-primary fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-4">
            <div class="card border-0 shadow-sm h-100 overflow-hidden" style="border-left: 5px solid #28a745 !important;">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="text-muted small text-uppercase fw-bold mb-1">Total Pedidos</p>
                            <h2 class="fw-bold mb-0 text-dark">${stats.orders.total}</h2>
                        </div>
                        <div class="bg-success bg-opacity-10 p-2 rounded">
                            <i class="bi bi-cart-check text-success fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-4">
            <div class="card border-0 shadow-sm h-100 overflow-hidden" style="border-left: 5px solid #17a2b8 !important;">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="text-muted small text-uppercase fw-bold mb-1">Visitas del Periodo</p>
                            <h2 class="fw-bold mb-0 text-dark">${stats.visits}</h2>
                            <div class="text-info small mt-1">
                                <i class="bi bi-person-check me-1"></i><strong>${stats.unique_visits}</strong> únicas
                            </div>
                        </div>
                        <div class="bg-info bg-opacity-10 p-2 rounded">
                            <i class="bi bi-eye text-info fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Breakdown Row -->
        <div class="col-12 mt-3">
            <div class="d-flex flex-wrap gap-2">
                ${Object.entries(stats.orders).map(([status, count]) => {
                    if (status === 'total') return '';
                    const label = STATUS_FLOW[status]?.label || status;
                    const color = statusClasses[status] || 'secondary';
                    return `
                        <div class="bg-white border rounded px-3 py-2 shadow-xs d-flex align-items-center">
                            <span class="badge bg-${color} rounded-circle me-2" style="width: 10px; height: 10px; padding: 0;">&nbsp;</span>
                            <span class="text-muted small me-2">${label}:</span>
                            <span class="fw-bold text-dark">${count}</span>
                        </div>
                    `;
                }).join('')}
            </div>
        </div>
    `;
}

async function loadOrders() {
    const tbody = document.getElementById('orders-table-body');
    const loadingEl = document.getElementById('orders-loading');
    const emptyEl = document.getElementById('orders-empty');

    // Reset view
    tbody.innerHTML = '';
    loadingEl.classList.remove('d-none');
    emptyEl.classList.add('d-none');

    const statusFilter = document.querySelector('input[name="statusFilter"]:checked').value;
    const search = document.getElementById('searchInput').value;

    const url = new URL('api/get_orders.php', window.location.href);
    if (statusFilter) url.searchParams.append('status', statusFilter);
    if (search) url.searchParams.append('search', search);

    try {
        const res = await fetch(url);
        const data = await res.json();
        
        loadingEl.classList.add('d-none');

        if (data.success) {
            currentOrders = data.orders;
            renderOrders(data.orders);
        } else {
            showToast(data.message, "error");
        }
    } catch (e) {
        loadingEl.classList.add('d-none');
        showToast("Error de conexión", "error");
    }
}

function renderOrders(orders) {
    const tbody = document.getElementById('orders-table-body');
    const emptyEl = document.getElementById('orders-empty');

    if (orders.length === 0) {
        emptyEl.classList.remove('d-none');
        return;
    }

    let html = '';
    orders.forEach(order => {
        const statusConfig = STATUS_FLOW[order.estado] || STATUS_FLOW['pendiente'];
        const isRechazada = order.estado === 'rechazada';
        
        // Actions Logic
        let actionsHtml = '';
        
        // View Details always available via icon
        const viewBtn = `<button class="btn btn-light btn-sm me-1 text-secondary" onclick="viewDetails(${order.cpu_id})" title="Ver Detalles"><i class="bi bi-eye"></i></button>`;

        if (!isRechazada && order.estado !== 'entregada') {
            // Prev Button
            if (statusConfig.prev) {
                 actionsHtml += `<button class="btn btn-outline-secondary btn-sm me-1" onclick="changeStatus(${order.cpu_id}, '${statusConfig.prev}')" title="Retroceder"><i class="bi bi-arrow-left"></i></button>`;
            }
            // Next Button
            if (statusConfig.next) {
                 actionsHtml += `<button class="btn btn-primary btn-sm me-1" onclick="tryChangeStatus(${order.cpu_id}, '${statusConfig.next}')" title="Avanzar"><i class="bi bi-arrow-right"></i></button>`;
            }
            // Reject Button
            actionsHtml += `<button class="btn btn-outline-danger btn-sm" onclick="openRejectModal(${order.cpu_id})" title="Rechazar"><i class="bi bi-x-lg"></i></button>`;
        } else if (isRechazada) {
             // Delete Button
             actionsHtml += `<button class="btn btn-danger btn-sm" onclick="deleteOrder(${order.cpu_id})" title="Eliminar definitivamente"><i class="bi bi-trash"></i></button>`;
        }

        let totalBs = parseFloat(order.valor_compra_bs)
        let bsEnvio = 0;
        
        if (order.entrega.tipo === 'delivery') {
            bsEnvio = parseFloat(order.importe_envio_bs);
            totalBs += bsEnvio;
        }



        let deliveryType = order.entrega.tipo === 'delivery' ? '<span class="badge bg-secondary">Delivery</span>' : '<span class="badge bg-light text-dark border">Retiro</span>';
        
        // Logic to show savings
        let savingsHtml = '<span class="text-muted small">-</span>';
        if (parseFloat(order.ahorrado) > 0) {
            savingsHtml = `
                <div class="text-success fw-bold small">Ahorro: $${parseFloat(order.ahorrado).toFixed(2)}</div>
                <div class="text-success small" style="font-size: 0.75rem;">${parseFloat(order.ahorrado_bs).toFixed(2)} Bs</div>
            `;
        }

        let totalBsFinal = totalBs - parseFloat(order.ahorrado_bs);

        html += `
        <tr class="animate-fade-in">
            <td class="ps-3"><span class="fw-bold text-dark">#${order.orden_id}</span></td>
            <td>
                <div class="fw-14 fw-bold text-dark">${order.cliente.nombre}</div>
                <div class="small text-muted"><i class="bi bi-telephone me-1"></i>${order.cliente.telefono}</div>
            </td>
            
            <td>
                <div class="small">${order.fecha_compra.split(' ')[0]}</div>
                <div class="small text-muted">${order.fecha_compra.split(' ')[1]}</div>
            </td>
            <td>
                ${deliveryType}
            </td>
            <td>
                <div >${bsEnvio} Bs</div>
            </td>
            <td>
                <div >${parseFloat(order.valor_compra_bs).toFixed(2)} Bs</div>
            </td>
            <td>
                <div>${totalBs.toFixed(2)} Bs</div>
            </td>
        
            <td>
                ${savingsHtml}
            </td>
            <td class="fw-bold text-success">
                ${totalBsFinal.toFixed(2)} Bs
            </td>
                <td class="fw-bold text-success">
                ${order.operacion.numero}
            </td>
                <td>
                <span class="badge ${statusConfig.class} rounded-pill">${statusConfig.label}</span>
                ${isRechazada ? `<div class="text-danger small mt-1" style="font-size:0.75rem; max-width: 150px; line-height:1.2;">${order.motivo_rechazo}</div>` : ''}
            </td>
            <td>
                <div class="d-flex align-items-center">
                    ${viewBtn}
                    ${actionsHtml}
                </div>
            </td>
        </tr>`;
    });
    tbody.innerHTML = html;
}

// Actions Handlers

window.tryChangeStatus = (id, newStatus) => {
    if (newStatus === 'entregada') {
        pendingStatusChange = { id, newStatus };
        const modal = new bootstrap.Modal(document.getElementById('modalConfirmDelivery'));
        modal.show();
    } else {
        changeStatus(id, newStatus);
    }
}

window.changeStatus = async (id, newStatus) => {
    try {
        const res = await fetch('api/update_status.php', {
            method: 'POST', 
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id, status: newStatus })
        });
        const data = await res.json();
        if (data.success) {
            showToast("Estado actualizado correctamente", "success");
            loadOrders();
            loadStats();
        } else {
            showToast(data.message, "error");
        }
    } catch (e) {
        showToast("Error al actualizar el estado", "error");
    }
}

window.confirmDelivery = async () => {
    if (pendingStatusChange) {
        // Cerrar modal
        const el = document.getElementById('modalConfirmDelivery');
        const modal = bootstrap.Modal.getInstance(el);
        modal.hide();

        await changeStatus(pendingStatusChange.id, pendingStatusChange.newStatus);
        pendingStatusChange = null;
    }
}

window.openRejectModal = (id) => {
    pendingRejectionId = id;
    document.getElementById('razonRechazo').value = '';
    const modal = new bootstrap.Modal(document.getElementById('modalReject'));
    modal.show();
}

window.confirmReject = async () => {
    const motivo = document.getElementById('razonRechazo').value.trim();
    if (!motivo) {
        showToast("Debes ingresar un motivo", "error");
        return;
    }
    
    // Hide modal
    const el = document.getElementById('modalReject');
    const modal = bootstrap.Modal.getInstance(el);
    modal.hide();

    try {
        const res = await fetch('api/reject_order.php', {
            method: 'POST', 
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: pendingRejectionId, motivo })
        });
        const data = await res.json();
        if (data.success) {
            showToast("Orden rechazada", "success");
            loadOrders();
            loadStats();
        } else {
            showToast(data.message, "error");
        }
    } catch (e) {
        showToast("Error rejecting", "error");
    }
}

window.deleteOrder = async (id) => {
    Notiflix.Confirm.show(
        'Eliminar Orden',
        '¿Realmente quieres eliminar esta orden del historial? Esta acción no se puede deshacer.',
        'Sí, eliminar',
        'Cancelar',
        async () => {
            try {
                const res = await fetch('api/delete_order.php', {
                    method: 'POST', 
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id })
                });
                const data = await res.json();
                if (data.success) {
                    showToast("Orden eliminada", "success");
                    loadOrders();
                    loadStats();
                } else {
                    showToast(data.message, "error");
                }
            } catch (e) {
                showToast("Error deleting", "error");
            }
        }
    );
}

let mapInstance = null;

window.viewDetails = (id) => {
    const order = currentOrders.find(o => o.cpu_id == id);
    if (!order) return;

    document.getElementById('detailOrderId').textContent = order.orden_id;
    document.getElementById('detailClientName').textContent = order.cliente.nombre;
    document.getElementById('detailClientEmail').textContent = order.cliente.email;
    document.getElementById('detailClientPhone').textContent = order.cliente.telefono;

    document.getElementById('detailOperacionNumero').textContent = order.operacion.numero;
    document.getElementById('detailOperacionHora').textContent = order.operacion.hora;
    
    // Delivery Details
    const deliveryContent = document.getElementById('detailDeliveryContent');
    const pickupContent = document.getElementById('detailPickupContent');
    const deliveryTypeBadge = document.getElementById('detailDeliveryType');
    
    if (order.entrega.tipo === 'delivery') {
        deliveryTypeBadge.textContent = 'Delivery';
        deliveryTypeBadge.className = 'badge bg-secondary mb-2';
        
        deliveryContent.classList.remove('d-none');
        pickupContent.classList.add('d-none');
        
        document.getElementById('detailReceptor').textContent = order.entrega.receptor || 'No especificado';
        document.getElementById('detailReceptorPhone').textContent = order.entrega.receptor ? (order.entrega.telefono || order.cliente.telefono) : order.cliente.telefono;
        document.getElementById('detailAddress').textContent = order.entrega.direccion;
        document.getElementById('detailRef').textContent = order.entrega.referencia || 'Sin referencia';
        
        // Map Logic
        const mapContainer = document.getElementById('deliveryMap');
        if (order.entrega.lat && order.entrega.lng) {
            mapContainer.classList.remove('d-none');
            
            // Wait for modal to handle layout then init map
            setTimeout(() => {
                if (mapInstance) {
                    mapInstance.remove();
                    mapInstance = null;
                }
                
                const lat = parseFloat(order.entrega.lat);
                const lng = parseFloat(order.entrega.lng);
                
                mapInstance = L.map('deliveryMap').setView([lat, lng], 15);
                
                L.tileLayer('https://{s}.google.com/vt/lyrs=m&x={x}&y={y}&z={z}', {
                    maxZoom: 20,
                    subdomains: ['mt0', 'mt1', 'mt2', 'mt3']
                }).addTo(mapInstance);
                
                L.marker([lat, lng]).addTo(mapInstance)
                    .bindPopup('<b>Ubicación de Entrega</b><br>' + order.entrega.direccion)
                    .openPopup();
                    
                mapInstance.invalidateSize();
            }, 300);
        } else {
            mapContainer.classList.add('d-none');
        }
        
    } else {
        deliveryTypeBadge.textContent = 'Retiro en Tienda';
        deliveryTypeBadge.className = 'badge bg-light text-dark border mb-2';
        
        deliveryContent.classList.add('d-none');
        pickupContent.classList.remove('d-none');
    }

    const tbody = document.getElementById('detailItemsTable');
    tbody.innerHTML = '';
    
    order.items.forEach(item => {
        tbody.innerHTML += `
            <tr>
                <td>${item.producto_nombre}</td>
                <td class="text-center">${item.quantity}</td>
                <td class="text-end">$${parseFloat(item.precio).toFixed(2)}</td>
                <td class="text-end">$${parseFloat(item.subtotal).toFixed(2)}</td>
            </tr>
        `;
    });
    
    const totalBs = parseFloat(order.valor_compra_bs) + parseFloat(order.importe_envio_bs); // Correct Sum
    const deliveryBs = parseFloat(order.importe_envio_bs);
    
    let deliveryRow = '';
    if (order.entrega.tipo === 'delivery') {
        deliveryRow = `
            <div class="d-flex justify-content-between text-muted small mb-1">
                <span>Envío:</span>
                <span>${deliveryBs.toFixed(2)} Bs</span>
            </div>
            <div class="d-flex justify-content-between text-muted small mb-2">
                <span>Compra:</span>
                <span>${parseFloat(order.valor_compra_bs).toFixed(2)} Bs</span>
            </div>
            <hr class="my-1">
        `;
    }

    document.getElementById('detailTotal').innerHTML = `
        <div class="small w-100">
            ${deliveryRow}
            <div class="d-flex justify-content-between align-items-center">
                <span>Total:</span>
                <span class="fs-5 text-success ms-2">${totalBs.toFixed(2)} Bs</span>
            </div>
            <div class="text-end text-muted small mt-1">Ref: $${parseFloat(order.total).toFixed(2)}</div>
        </div>
    `;

    const modal = new bootstrap.Modal(document.getElementById('modalOrderDetails'));
    modal.show();
    
    // Clean map on close if needed
    document.getElementById('modalOrderDetails').addEventListener('hidden.bs.modal', function () {
        if (mapInstance) {
            mapInstance.remove();
            mapInstance = null;
        }
    });
}

function showToast(msg, type = 'info') {
    Toastify({
        text: msg,
        duration: 3000,
        gravity: "top", 
        position: "right", 
        style: {
            background: type === 'success' ? "green" : (type === 'error' ? "red" : "blue"),
        }
    }).showToast();
}
