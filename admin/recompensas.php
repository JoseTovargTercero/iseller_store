<?php
require_once 'includes/session.php';
requireAdminLogin();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recompensas Disponibles - iSeller Admin</title>
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
    <style>
        .reward-count-badge {
            font-size: 0.75rem;
            padding: 4px 10px;
        }
        .action-row td { vertical-align: middle; }
        #banner-info {
            background: linear-gradient(135deg, #eafaf0 0%, #d4f5e2 100%);
            border: 1px solid #a8e6c0;
            border-radius: 12px;
        }
    </style>
</head>
<body>

    <?php include 'includes/nav.php'; ?>

    <div class="container-fluid px-4 py-4">

        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-3">
            <div>
                <h1 class="h3 fw-bold mb-1">
                    <i class="bi bi-gift-fill text-success me-2"></i>Recompensas Disponibles
                </h1>
                <p class="text-muted mb-0 small">
                    Usuarios que tienen al menos una recompensa en estado <span class="badge bg-success bg-opacity-10 text-success fw-semibold">disponible</span>.
                    Envíales un correo para animarlos a usarla.
                </p>
            </div>
            <button class="btn btn-success rounded-pill px-4 fw-semibold" id="btnSendAll" disabled>
                <i class="bi bi-send-fill me-2"></i>Enviar a todos
                <span class="badge bg-white text-success ms-2 fw-bold" id="totalCountBadge">0</span>
            </button>
        </div>

        <!-- Info Banner -->
        <div id="banner-info" class="p-3 mb-4 d-flex align-items-center gap-3">
            <span style="font-size:2rem;">📧</span>
            <div>
                <strong>Correo de notificación</strong><br>
                <span class="text-muted small">Se enviará desde <code>contacto@iseller-tiendas.com</code> con el asunto
                <em>"Tienes una recompensa lista para usar — no la dejes pasar"</em>.</span>
            </div>
        </div>

        <!-- Table Card -->
        <div class="card border-0 shadow-sm overflow-hidden">
            <div class="card-header bg-white d-flex justify-content-between align-items-center py-3 border-bottom">
                <span class="fw-semibold">
                    <i class="bi bi-people me-2 text-muted"></i>Lista de usuarios
                </span>
                <div class="input-group" style="max-width:280px;">
                    <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" class="form-control border-start-0 ps-0" id="searchInput" placeholder="Buscar nombre o email…">
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light text-muted small text-uppercase">
                        <tr>
                            <th class="ps-4 py-3">
                                <div class="form-check mb-0">
                                    <input class="form-check-input" type="checkbox" id="checkAll">
                                </div>
                            </th>
                            <th class="py-3">Cliente</th>
                            <th class="py-3">Email</th>
                            <th class="py-3 text-center">Recompensas disp.</th>
                            <th class="py-3 text-end pe-4">Acción</th>
                        </tr>
                    </thead>
                    <tbody id="rewardsTable">
                        <tr>
                            <td colspan="5" class="text-center py-5">
                                <div class="spinner-border text-success"></div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Empty / Error States (hidden by default) -->
        <div id="stateEmpty" class="text-center py-5 d-none">
            <i class="bi bi-inbox fs-1 text-muted"></i>
            <p class="text-muted mt-3">No hay usuarios con recompensas disponibles en este momento.</p>
        </div>
        <div id="stateError" class="text-center py-5 d-none">
            <i class="bi bi-exclamation-triangle fs-1 text-danger"></i>
            <p class="text-danger mt-3">Error al cargar los datos. Intenta recargar la página.</p>
        </div>

    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/dist/notiflix-Notiflix-67ba12d/dist/notiflix-3.2.7.min.js"></script>
    <script src="assets/js/app.js"></script>
    <script>
        let allUsers = [];

        /* ─── Load users on page load ─── */
        document.addEventListener('DOMContentLoaded', () => {
            loadUsers();

            // Search filter
            let timer;
            document.getElementById('searchInput').oninput = (e) => {
                clearTimeout(timer);
                timer = setTimeout(() => filterTable(e.target.value), 300);
            };

            // Check-all toggle
            document.getElementById('checkAll').onchange = (e) => {
                document.querySelectorAll('.row-check').forEach(cb => cb.checked = e.target.checked);
                updateSendAllBtn();
            };

            // Send to all selected
            document.getElementById('btnSendAll').onclick = () => {
                const checked = [...document.querySelectorAll('.row-check:checked')].map(cb => parseInt(cb.dataset.id));
                if (!checked.length) return;
                confirmAndSend(checked, `¿Enviar correo a los <strong>${checked.length}</strong> usuario(s) seleccionados?`);
            };
        });

        /* ─── Fetch users ─── */
        async function loadUsers() {
            try {
                const res = await fetch('api/get_rewards_users.php');
                const data = await res.json();

                if (data.success) {
                    allUsers = data.users;
                    renderTable(allUsers);
                } else {
                    showState('error');
                }
            } catch (e) {
                showState('error');
            }
        }

        /* ─── Render table ─── */
        function renderTable(users) {
            const tbody = document.getElementById('rewardsTable');
            const total = document.getElementById('totalCountBadge');

            if (users.length === 0) {
                showState('empty');
                tbody.innerHTML = '';
                total.textContent = '0';
                document.getElementById('btnSendAll').disabled = true;
                return;
            }

            document.getElementById('stateEmpty').classList.add('d-none');
            document.getElementById('stateError').classList.add('d-none');
            document.querySelector('.card').classList.remove('d-none');

            total.textContent = users.length;
            document.getElementById('btnSendAll').disabled = false;

            tbody.innerHTML = users.map(u => `
                <tr class="action-row">
                    <td class="ps-4">
                        <div class="form-check mb-0">
                            <input class="form-check-input row-check" type="checkbox" data-id="${u.id}" onchange="updateSendAllBtn()">
                        </div>
                    </td>
                    <td>
                        <div class="fw-semibold text-dark">${escHtml(u.nombre || 'Sin nombre')}</div>
                        <div class="text-muted extra-small">ID #${u.id}</div>
                    </td>
                    <td>
                        <span class="text-muted small">
                            <i class="bi bi-envelope me-1"></i>${escHtml(u.email)}
                        </span>
                    </td>
                    <td class="text-center">
                        <span class="badge bg-success bg-opacity-10 text-success fw-semibold reward-count-badge">
                            <i class="bi bi-gift me-1"></i>${u.total_disponibles}
                        </span>
                    </td>
                    <td class="text-end pe-4">
                        <button class="btn btn-outline-success btn-sm rounded-pill px-3"
                                onclick="sendToUser(${u.id}, '${escJs(u.nombre || 'Sin nombre')}', this)">
                            <i class="bi bi-send me-1"></i>Enviar correo
                        </button>
                    </td>
                </tr>
            `).join('');
        }

        /* ─── Filter by search ─── */
        function filterTable(q) {
            const term = q.toLowerCase().trim();
            const filtered = term
                ? allUsers.filter(u =>
                    (u.nombre || '').toLowerCase().includes(term) ||
                    (u.email || '').toLowerCase().includes(term))
                : allUsers;
            renderTable(filtered);
        }

        /* ─── Send to single user ─── */
        function sendToUser(userId, nombre, btn) {
            confirmAndSend([userId], `¿Enviar correo a <strong>${escHtml(nombre)}</strong>?`, btn);
        }

        /* ─── Confirm & send ─── */
        function confirmAndSend(userIds, message, triggerBtn = null) {
            Notiflix.Confirm.show(
                'Confirmar envío',
                message,
                'Sí, enviar',
                'Cancelar',
                async () => {
                    if (triggerBtn) {
                        triggerBtn.disabled = true;
                        triggerBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Enviando…';
                    }

                    try {
                        const res = await fetch('api/send_rewards_email.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ user_ids: userIds })
                        });
                        const data = await res.json();

                        if (data.success) {
                            if (data.sent > 0) {
                                Notiflix.Notify.success(
                                    `✅ ${data.sent} correo(s) enviado(s) exitosamente.`,
                                    { timeout: 4000 }
                                );
                                if (triggerBtn) {
                                    triggerBtn.innerHTML = '<i class="bi bi-check-circle-fill me-1"></i>Enviado';
                                    triggerBtn.classList.replace('btn-outline-success', 'btn-success');
                                }
                            }
                            if (data.failed > 0) {
                                Notiflix.Notify.warning(
                                    `⚠️ ${data.failed} correo(s) no pudieron enviarse.`,
                                    { timeout: 5000 }
                                );
                            }
                        } else {
                            Notiflix.Notify.failure(data.message || 'Error al enviar correos');
                            if (triggerBtn) resetBtn(triggerBtn);
                        }
                    } catch (e) {
                        Notiflix.Notify.failure('Error de conexión con el servidor');
                        if (triggerBtn) resetBtn(triggerBtn);
                    }
                },
                () => {
                    // cancelled — restore button
                    if (triggerBtn) resetBtn(triggerBtn);
                },
                {
                    titleColor: '#2e6b42',
                    okButtonBackground: '#5a9e6f',
                    okButtonColor: '#fff',
                }
            );
        }

        function resetBtn(btn) {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-send me-1"></i>Enviar correo';
        }

        /* ─── Update "send all" button state ─── */
        function updateSendAllBtn() {
            const anyChecked = document.querySelectorAll('.row-check:checked').length > 0;
            document.getElementById('btnSendAll').disabled = !anyChecked;
        }

        /* ─── Show empty/error states ─── */
        function showState(state) {
            document.querySelector('.card').classList.add('d-none');
            if (state === 'empty') {
                document.getElementById('stateEmpty').classList.remove('d-none');
                document.getElementById('stateError').classList.add('d-none');
            } else {
                document.getElementById('stateError').classList.remove('d-none');
                document.getElementById('stateEmpty').classList.add('d-none');
            }
        }

        /* ─── Utilities ─── */
        function escHtml(str) {
            return String(str)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;');
        }
        function escJs(str) {
            return String(str).replace(/'/g, "\\'").replace(/"/g, '\\"');
        }
    </script>
</body>
</html>
