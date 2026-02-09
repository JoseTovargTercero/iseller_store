<?php
/**
 * Panel de Administración de Chat
 * Gestión de conversaciones y mensajes
 */
require_once 'includes/session.php';
requireAdminLogin();


?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Chat - Admin</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <meta name="csrf-token" content="<?php echo getCSRFToken(); ?>">
    
    <style>
        :root {
            --primary-color: #10b981;
            --primary-dark: #059669;
            --sidebar-width: 380px;
        }
        
        body {
            background: #f3f4f6;
            overflow: hidden;
        }
        
        .admin-container {
            display: flex;
            height: 100vh;
        }
        
        /* Sidebar - Lista de conversaciones */
        .conversations-sidebar {
            width: var(--sidebar-width);
            background: white;
            border-right: 1px solid #e5e7eb;
            display: flex;
            flex-direction: column;
        }
        
        .sidebar-header {
            padding: 1.5rem;
            border-bottom: 1px solid #e5e7eb;
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
        }
        
        .sidebar-filters {
            padding: 1rem;
            border-bottom: 1px solid #e5e7eb;
            background: #f9fafb;
        }
        
        .conversations-list {
            flex: 1;
            overflow-y: auto;
        }
        
        .conversation-item {
            padding: 1rem 1.5rem;
            border-bottom: 1px solid #e5e7eb;
            cursor: pointer;
            transition: all 0.2s;
            position: relative;
        }
        
        .conversation-item:hover {
            background: #f9fafb;
        }
        
        .conversation-item.active {
            background: #ecfdf5;
            border-left: 4px solid var(--primary-color);
        }
        
        .conversation-item.unread {
            background: #fef3c7;
        }
        
        .conv-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 0.5rem;
        }
        
        .conv-user {
            font-weight: 600;
            color: #111827;
            font-size: 0.95rem;
        }
        
        .conv-time {
            font-size: 0.75rem;
            color: #6b7280;
        }
        
        .conv-subject {
            font-size: 0.85rem;
            color: #4b5563;
            margin-bottom: 0.25rem;
            display: -webkit-box;
            -webkit-line-clamp: 1;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .conv-preview {
            font-size: 0.8rem;
            color: #9ca3af;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .conv-meta {
            display: flex;
            gap: 0.5rem;
            margin-top: 0.5rem;
        }
        
        .conv-badge {
            font-size: 0.7rem;
            padding: 0.2rem 0.5rem;
            border-radius: 99px;
            font-weight: 600;
        }
        
        .badge-abierta { background: #dbeafe; color: #1e40af; }
        .badge-cerrada { background: #e5e7eb; color: #6b7280; }
        .badge-resuelta { background: #d1fae5; color: #065f46; }
        
        .unread-badge {
            background: #ef4444;
            color: white;
            font-size: 0.7rem;
            padding: 0.2rem 0.5rem;
            border-radius: 99px;
            font-weight: 600;
        }
        
        /* Chat Area */
        .chat-area {
            flex: 1;
            display: flex;
            flex-direction: column;
            background: white;
        }
        
        .chat-header {
            padding: 1.5rem;
            border-bottom: 1px solid #e5e7eb;
            background: white;
        }
        
        .chat-user-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .chat-messages {
            flex: 1;
            overflow-y: auto;
            padding: 1.5rem;
            background: #f9fafb;
        }
        
        .message {
            margin-bottom: 1rem;
            display: flex;
            gap: 0.75rem;
        }
        
        .message.user {
            flex-direction: row-reverse;
        }
        
        .message-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            flex-shrink: 0;
        }
        
        .message.admin .message-avatar {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
        }
        
        .message.user .message-avatar {
            background: #dbeafe;
            color: #1e40af;
        }
        
        .message-content {
            max-width: 70%;
        }
        
        .message-bubble {
            padding: 0.75rem 1rem;
            border-radius: 12px;
            margin-bottom: 0.25rem;
        }
        
        .message.admin .message-bubble {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            border-bottom-left-radius: 4px;
        }
        
        .message.user .message-bubble {
            background: white;
            color: #111827;
            border: 1px solid #e5e7eb;
            border-bottom-right-radius: 4px;
        }
        
        .message-time {
            font-size: 0.75rem;
            color: #9ca3af;
            padding: 0 0.5rem;
        }
        
        .chat-input-area {
            padding: 1.5rem;
            border-top: 1px solid #e5e7eb;
            background: white;
        }
        
        .input-wrapper {
            display: flex;
            gap: 0.75rem;
        }
        
        .message-input {
            flex: 1;
            border: 1px solid #e5e7eb;
            border-radius: 24px;
            padding: 0.75rem 1.25rem;
            resize: none;
            font-size: 0.95rem;
            max-height: 120px;
        }
        
        .message-input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
        }
        
        .send-btn {
            background: var(--primary-color);
            color: white;
            border: none;
            width: 48px;
            height: 48px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .send-btn:hover {
            background: var(--primary-dark);
            transform: scale(1.05);
        }
        
        .send-btn:disabled {
            background: #d1d5db;
            cursor: not-allowed;
            transform: none;
        }
        
        .empty-state {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
            color: #9ca3af;
        }
        
        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }
        
        .stats-row {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }
        
        .stat-badge {
            flex: 1;
            padding: 0.5rem;
            border-radius: 8px;
            text-align: center;
            font-size: 0.85rem;
        }
        
        .stat-badge.primary {
            background: #dbeafe;
            color: #1e40af;
        }
        
        .stat-badge.success {
            background: #d1fae5;
            color: #065f46;
        }
        
        .stat-badge.secondary {
            background: #e5e7eb;
            color: #6b7280;
        }
        
        .loading {
            text-align: center;
            padding: 2rem;
            color: #9ca3af;
        }
        
        .spinner {
            border: 3px solid #f3f4f6;
            border-top: 3px solid var(--primary-color);
            border-radius: 50%;
            width: 36px;
            height: 36px;
            animation: spin 1s linear infinite;
            margin: 0 auto 1rem;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar - Conversaciones -->
        <div class="conversations-sidebar">
            <div class="sidebar-header">
                <h4 class="mb-1"><i class="bi bi-chat-dots-fill me-2"></i>Chat Admin</h4>
                <small class="opacity-75">Gestión de conversaciones</small>
            </div>
            
            <!-- Estadísticas -->
            <div class="sidebar-filters">
                <div class="stats-row" id="stats-row">
                    <div class="stat-badge primary">
                        <div class="fw-bold" id="stat-abiertas">0</div>
                        <small>Abiertas</small>
                    </div>
                    <div class="stat-badge success">
                        <div class="fw-bold" id="stat-resueltas">0</div>
                        <small>Resueltas</small>
                    </div>
                    <div class="stat-badge secondary">
                        <div class="fw-bold" id="stat-cerradas">0</div>
                        <small>Cerradas</small>
                    </div>
                </div>
                
                <!-- Filtros -->
                <div class="input-group input-group-sm mb-2">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="text" class="form-control" id="search-input" placeholder="Buscar...">
                </div>
                
                <select class="form-select form-select-sm" id="filter-estado">
                    <option value="">Todos los estados</option>
                    <option value="abierta">Abiertas</option>
                    <option value="resuelta">Resueltas</option>
                    <option value="cerrada">Cerradas</option>
                </select>
            </div>
            
            <!-- Lista de conversaciones -->
            <div class="conversations-list" id="conversations-list">
                <div class="loading">
                    <div class="spinner"></div>
                    <p>Cargando conversaciones...</p>
                </div>
            </div>
        </div>
        
        <!-- Área de Chat -->
        <div class="chat-area" id="chat-area">
            <div class="empty-state">
                <i class="bi bi-chat-text"></i>
                <h5>Selecciona una conversación</h5>
                <p>Elige una conversación de la lista para ver los mensajes</p>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/app.js"></script>
    
    <script>
        let conversaciones = [];
        let conversacionActiva = null;
        let mensajes = [];
        let pollingInterval = null;
        let lastMessageId = 0;
        let currentAbortController = null;
        
        // Cargar conversaciones
        async function cargarConversaciones(mostrarLoading = true) {
            const estado = document.getElementById('filter-estado').value;
            const busqueda = document.getElementById('search-input').value;
            
            if (mostrarLoading) {
                document.getElementById('conversations-list').innerHTML = '<div class="loading"><div class="spinner"></div><p>Cargando conversaciones...</p></div>';
            }
            
            try {
                let url = '../api/admin/chat/obtener_conversaciones.php?';
                if (estado) url += `estado=${estado}&`;
                if (busqueda) url += `busqueda=${encodeURIComponent(busqueda)}&`;
                const response = await fetch(url);
                const data = await response.json();
                
                if (data.success) {
                    conversaciones = data.conversaciones;
                    renderConversaciones();
                    actualizarEstadisticas(data.estadisticas);
                }
            } catch (error) {
                console.error('Error cargando conversaciones:', error);
            }
        }
        
        function renderConversaciones() {
            const container = document.getElementById('conversations-list');
            
            if (conversaciones.length === 0) {
                container.innerHTML = '<div class="empty-state"><p>No hay conversaciones</p></div>';
                return;
            }
            
            container.innerHTML = '';
            
            conversaciones.forEach(conv => {
                const item = document.createElement('div');
                const isActive = conversacionActiva?.id === conv.id;
                item.className = `conversation-item ${conv.mensajes_sin_leer > 0 ? 'unread' : ''} ${isActive ? 'active' : ''}`;
                
                item.innerHTML = `
                    <div class="conv-header">
                        <div class="conv-user">${escapeHtml(conv.usuario_nombre)}</div>
                        <div class="conv-time">${formatearFecha(conv.ultimo_mensaje_en)}</div>
                    </div>
                    <div class="conv-subject">${escapeHtml(conv.asunto)}</div>
                    <div class="conv-preview">${escapeHtml(conv.ultimo_mensaje || '')}</div>
                    <div class="conv-meta">
                        <span class="conv-badge badge-${conv.estado}">${conv.estado}</span>
                        <span class="conv-badge" style="background: #f3f4f6; color: #6b7280;">
                            <i class="bi bi-chat-fill me-1"></i>${conv.total_mensajes}
                        </span>
                        ${conv.mensajes_sin_leer > 0 ? `<span class="unread-badge">${conv.mensajes_sin_leer} nuevos</span>` : ''}
                    </div>
                `;
                
                item.onclick = () => seleccionarConversacion(conv);
                container.appendChild(item);
            });
        }
        
        function actualizarEstadisticas(stats) {
            document.getElementById('stat-abiertas').textContent = stats.abiertas;
            document.getElementById('stat-resueltas').textContent = stats.resueltas;
            document.getElementById('stat-cerradas').textContent = stats.cerradas;
        }
        
        async function seleccionarConversacion(conv) {
            // No hacer nada si ya es la activa
            if (conversacionActiva?.id === conv.id) return;
            
            conversacionActiva = conv;
            
            // Detener polling previo y peticiones en curso
            detenerPolling();
            
            // UI Feedback
            renderConversaciones();
            const chatArea = document.getElementById('chat-area');
            chatArea.innerHTML = `
                <div class="empty-state">
                    <div class="spinner"></div>
                    <h5>Cargando mensajes...</h5>
                </div>
            `;
            
            // Reset state
            mensajes = [];
            lastMessageId = 0;
            
            await cargarMensajes(conv.id);
            renderChatArea();
            iniciarPolling();
        }
        
        async function cargarMensajes(conversacionId) {
            try {
                // Cancelar peticiones previas si existen
                if (currentAbortController) currentAbortController.abort();
                currentAbortController = new AbortController();
                
                const response = await fetch(`../api/admin/chat/obtener_mensajes.php?conversacion_id=${conversacionId}`, {
                    signal: currentAbortController.signal
                });
                const data = await response.json();
                
                if (data.success) {
                    mensajes = data.mensajes;
                    if (mensajes.length > 0) {
                        lastMessageId = mensajes[mensajes.length - 1].id;
                    } else {
                        lastMessageId = 0;
                    }
                    renderMensajes();
                }
            } catch (error) {
                if (error.name === 'AbortError') return;
                console.error('Error cargando mensajes:', error);
            }
        }
        
        function renderChatArea() {
            const chatArea = document.getElementById('chat-area');
            
            if (!conversacionActiva) {
                chatArea.innerHTML = '<div class="empty-state"><i class="bi bi-chat-text"></i><h5>Selecciona una conversación</h5></div>';
                return;
            }
            
            chatArea.innerHTML = `
                <div class="chat-header">
                    <div class="chat-user-info">
                        <div>
                            <h5 class="mb-1">${escapeHtml(conversacionActiva.usuario_nombre)}</h5>
                            <small class="text-muted">${escapeHtml(conversacionActiva.usuario_email)}</small>
                            <div class="mt-1"><strong>Asunto:</strong> ${escapeHtml(conversacionActiva.asunto)}</div>
                        </div>
                        <div class="d-flex gap-2">
                            <select class="form-select form-select-sm" id="estado-select" style="width: auto;">
                                <option value="abierta" ${conversacionActiva.estado === 'abierta' ? 'selected' : ''}>Abierta</option>
                                <option value="resuelta" ${conversacionActiva.estado === 'resuelta' ? 'selected' : ''}>Resuelta</option>
                                <option value="cerrada" ${conversacionActiva.estado === 'cerrada' ? 'selected' : ''}>Cerrada</option>
                            </select>
                            <button class="btn btn-sm btn-outline-secondary" onclick="cambiarEstado()">
                                <i class="bi bi-check-circle"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="chat-messages" id="chat-messages"></div>
                <div class="chat-input-area">
                    <div class="input-wrapper">
                        <textarea class="message-input" id="message-input" placeholder="Escribe tu respuesta..." rows="1"></textarea>
                        <button class="send-btn" id="send-btn" onclick="enviarMensaje()">
                            <i class="bi bi-send-fill"></i>
                        </button>
                    </div>
                </div>
            `;
            
            renderMensajes();
        }
        
        function renderMensajes() {
            const container = document.getElementById('chat-messages');
            if (!container) return;
            
            if (mensajes.length === 0) {
                container.innerHTML = '<div class="empty-state"><p>Sin mensajes aún</p></div>';
                return;
            }
            
            container.innerHTML = '';
            
            mensajes.forEach(msg => {
                const div = document.createElement('div');
                div.className = `message ${msg.es_admin ? 'admin' : 'user'}`;
                
                const iniciales = msg.es_admin ? 'AD' : conversacionActiva.usuario_nombre.substring(0, 2).toUpperCase();
                
                div.innerHTML = `
                    <div class="message-avatar">${iniciales}</div>
                    <div class="message-content">
                        <div class="message-bubble">${escapeHtml(msg.mensaje)}</div>
                        <div class="message-time">${formatearFecha(msg.creado_en)}</div>
                    </div>
                `;
                
                container.appendChild(div);
            });
            
            container.scrollTop = container.scrollHeight;
        }
        
        async function enviarMensaje() {
            const input = document.getElementById('message-input');
            const btn = document.getElementById('send-btn');
            const mensaje = input.value.trim();
            
            if (!mensaje || !conversacionActiva) return;
            
            input.disabled = true;
            btn.disabled = true;
            
            try {
                const response = await fetch('../api/admin/chat/responder_mensaje.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        conversacion_id: conversacionActiva.id,
                        mensaje: mensaje
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    input.value = '';
                    await cargarMensajes(conversacionActiva.id);
                } else {
                    alert(data.message || 'Error al enviar mensaje');
                }
            } catch (error) {
                console.error('Error enviando mensaje:', error);
                alert('Error al enviar mensaje');
            } finally {
                input.disabled = false;
                btn.disabled = false;
                input.focus();
            }
        }
        
        async function cambiarEstado() {
            const nuevoEstado = document.getElementById('estado-select').value;
            
            try {
                const response = await fetch('../api/admin/chat/cambiar_estado.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        conversacion_id: conversacionActiva.id,
                        estado: nuevoEstado
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    conversacionActiva.estado = nuevoEstado;
                    await cargarConversaciones(false);
                }
            } catch (error) {
                console.error('Error cambiando estado:', error);
            }
        }
        
        function iniciarPolling() {
            detenerPolling();
            poll();
        }
        
        function detenerPolling() {
            if (pollingInterval) {
                clearTimeout(pollingInterval);
                pollingInterval = null;
            }
            if (currentAbortController) {
                currentAbortController.abort();
                currentAbortController = null;
            }
        }
        
        async function poll() {
            if (!conversacionActiva) return;
            
            currentAbortController = new AbortController();
            
            try {
                const response = await fetch(
                    `../api/admin/chat/poll_mensajes.php?conversacion_id=${conversacionActiva.id}&ultimo_mensaje_id=${lastMessageId}`,
                    { signal: currentAbortController.signal }
                );
                const data = await response.json();
                
                if (data.success && data.nuevos_mensajes && data.mensajes.length > 0) {
                    data.mensajes.forEach(msg => {
                        // Evitar duplicados si llegaron por carga manual
                        if (!mensajes.find(m => m.id === msg.id)) {
                            mensajes.push(msg);
                            lastMessageId = msg.id;
                        }
                    });
                    renderMensajes();
                }
            } catch (error) {
                if (error.name === 'AbortError') return;
                console.error('Polling error:', error);
            }
            
            pollingInterval = setTimeout(() => poll(), 2000);
        }
        
        function formatearFecha(fecha) {
            const d = new Date(fecha);
            const ahora = new Date();
            const diff = ahora - d;
            const minutos = Math.floor(diff / 60000);
            
            if (minutos < 1) return 'Ahora';
            if (minutos < 60) return `Hace ${minutos}m`;
            if (minutos < 1440) return `Hace ${Math.floor(minutos / 60)}h`;
            
            return d.toLocaleDateString('es-ES', { day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' });
        }
        
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        // Event listeners
        document.getElementById('filter-estado').addEventListener('change', cargarConversaciones);
        
        let searchTimeout;
        document.getElementById('search-input').addEventListener('input', () => {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(cargarConversaciones, 500);
        });
        
        // Cargar al inicio
        cargarConversaciones();
        
        // Actualizar cada 30 segundos
        setInterval(cargarConversaciones, 30000);
    </script>
</body>
</html>
