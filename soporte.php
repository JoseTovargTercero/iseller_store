<?php
require_once('core/check_maintenance.php');
require_once('core/db.php');
require_once('core/session.php');

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// Configuración básica para el componente de chat
$userName = getUserName();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Soporte - iSeller Store</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <!-- Custom Styles -->
    <link rel="stylesheet" href="assets/css/global-styles.css">
    <link rel="stylesheet" href="assets/css/chat.css">
    
    <style>
        body {
            background-color: #f8fafc;
            display: flex;
            flex-direction: column;
            height: 100vh;
            margin: 0;
            overflow: hidden;
        }
        
        /* Forzar que el modal de chat sea el centro de la página y estático */
        .chat-modal-overlay {
            position: relative !important;
            display: flex !important;
            align-items: center;
            justify-content: center;
            background: transparent !important;
            opacity: 1 !important;
            visibility: visible !important;
            height: 100vh;
            width: 100%;
            z-index: 10;
        }
        
        .chat-modal {
            position: relative !important;
            bottom: auto !important;
            right: auto !important;
            width: 100% !important;
            max-width: 600px !important;
            height: 90vh !important;
            max-height: 800px !important;
            transform: none !important;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1) !important;
        }
        
        .chat-floating-button {
            display: none !important; /* No necesitamos el botón flotante aquí */
        }
        
        #back-to-store {
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 100;
        }
        
        @media (max-width: 768px) {
            .chat-modal {
                height: 100vh !important;
                max-height: none !important;
                border-radius: 0 !important;
            }
            #back-to-store {
                top: 10px;
                left: 10px;
            }
        }
    </style>
</head>
<body data-user-logged-in="true">

    <a href="index.php" id="back-to-store" class="btn btn-light rounded-pill shadow-sm">
        <i class="bi bi-arrow-left me-2"></i> Volver a la tienda
    </a>

    <?php include 'assets/components/chat.html'; ?>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/chat.js"></script>
    
    <script>
        // Inicializar chat y gestionar visibilidad
        document.addEventListener('DOMContentLoaded', () => {
            const tryInitChat = () => {
                if (window.chatSystem) {
                    window.chatSystem.openChat();
                } else {
                    setTimeout(tryInitChat, 100);
                }
            };
            tryInitChat();
        });
    </script>
</body>
</html>
