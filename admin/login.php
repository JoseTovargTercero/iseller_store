<?php
require_once 'includes/session.php';
if (isAdminLoggedIn()) {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - iSeller</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body { background: #f3f4f6; height: 100vh; display: flex; align-items: center; justify-content: center; font-family: 'Segoe UI', sans-serif; }
        .login-card { width: 100%; max-width: 400px; border: none; shadow: 0 4px 6px -1px rgba(0,0,0,0.1); border-radius: 1rem; }
        .card-header { background: transparent; border-bottom: none; padding-top: 2rem; text-align: center; }
        .btn-primary { background-color: #4F46E5; border-color: #4F46E5; }
        .btn-primary:hover { background-color: #4338ca; border-color: #4338ca; }
    </style>
    <meta name="csrf-token" content="<?php echo getCSRFToken(); ?>">
</head>
<body>
    <div class="card login-card shadow-lg p-3">
        <div class="card-header">
            <h1 class="h3 fw-bold text-dark"><i class="bi bi-shield-lock-fill text-primary"></i> Admin Panel</h1>
            <p class="text-muted small">iSeller Store Management</p>
        </div>
        <div class="card-body">
            <form id="loginForm">
                <div class="mb-3">
                    <label class="form-label text-uppercase small fw-bold text-muted">Usuario</label>
                    <input type="text" class="form-control form-control-lg" name="usuario" required autofocus>
                </div>
                <div class="mb-4">
                    <label class="form-label text-uppercase small fw-bold text-muted">Contraseña</label>
                    <input type="password" class="form-control form-control-lg" name="password" required>
                </div>
                <div class="d-grid">
                    <button type="submit" class="btn btn-primary btn-lg" id="btnLogin">
                        <span id="btnText">Ingresar</span>
                        <div class="spinner-border spinner-border-sm d-none" id="btnSpinner" role="status"></div>
                    </button>
                </div>
                <div class="mt-3 text-center">
                    <div id="alertMsg" class="alert alert-danger d-none p-2 small"></div>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.getElementById('loginForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const btn = document.getElementById('btnLogin');
            const btnText = document.getElementById('btnText');
            const spinner = document.getElementById('btnSpinner');
            const alertMsg = document.getElementById('alertMsg');

            btn.disabled = true;
            btnText.classList.add('d-none');
            spinner.classList.remove('d-none');
            alertMsg.classList.add('d-none');

            const formData = new FormData(this);
            const data = Object.fromEntries(formData.entries());
            data.action = 'login';

            try {
                const res = await fetch('api/auth.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({
                        ...data,
                        csrf_token: document.querySelector('meta[name="csrf-token"]').content
                    })
                });
                const json = await res.json();
                
                if (json.success) {
                    window.location.href = json.redirect;
                } else {
                    alertMsg.textContent = json.message;
                    alertMsg.classList.remove('d-none');
                    btn.disabled = false;
                    btnText.classList.remove('d-none');
                    spinner.classList.add('d-none');
                }
            } catch (error) {
                console.error(error);
                alertMsg.textContent = "Error de conexión";
                alertMsg.classList.remove('d-none');
                btn.disabled = false;
                btnText.classList.remove('d-none');
                spinner.classList.add('d-none');
            }
        });
    </script>
</body>
</html>
