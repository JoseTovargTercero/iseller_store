/**
 * Sistema de Chat Integrado - iSeller Store
 * Gestiona toda la lógica del chat en tiempo real
 */

class ChatSystem {
    constructor() {
        this.currentView = 'start';
        this.currentConversationId = null;
        this.lastMessageId = 0;
        this.pollingInterval = null;
        this.storeConfig = null;
        this.categories = [];
        this.conversations = [];
        
        this.init();
    }
    
    async init() {
        // Cargar configuración de la tienda
        await this.loadStoreConfig();
        
        // Cargar categorías
        await this.loadCategories();
        
        // Cargar contador de mensajes no leídos
        await this.updateUnreadBadge();
        
        // Configurar event listeners
        this.setupEventListeners();
        
        //console.log('Chat System initialized');
    }
    
    /* ================================
       CONFIGURACIÓN Y CARGA INICIAL
       ================================ */
    
    async loadStoreConfig() {
        try {
            const response = await fetch('api/chat/obtener_config_tienda.php');
            const data = await response.json();
            
            if (data.success) {
                this.storeConfig = data.config;
                this.updateHeaderInfo();
            }
        } catch (error) {
            console.error('Error loading store config:', error);
        }
    }
    
    async loadCategories() {
        try {
            const response = await fetch('api/chat/obtener_categorias.php');
            const data = await response.json();
            
            if (data.success) {
                this.categories = data.categorias;
                this.renderCategorySelect();
            }
        } catch (error) {
            console.error('Error loading categories:', error);
        }
    }
    
    updateHeaderInfo() {
        if (!this.storeConfig) return;
        
        const titleEl = document.getElementById('chat-header-title');
        const horarioEl = document.getElementById('chat-horario');
        const tiempoEl = document.getElementById('chat-tiempo-respuesta');
        
        if (titleEl) titleEl.textContent = this.storeConfig.nombre_comercial;
        if (horarioEl) horarioEl.textContent = this.storeConfig.horario_atencion;
        if (tiempoEl) tiempoEl.textContent = this.storeConfig.tiempo_respuesta_estimado;
    }
    
    renderCategorySelect() {
        const select = document.getElementById('chat-category-select');
        if (!select) return;
        
        select.innerHTML = '<option value="">Selecciona una categoría...</option>';
        
        this.categories.forEach(cat => {
            const option = document.createElement('option');
            option.value = cat.id;
            option.textContent = `${cat.icono ? this.getIconText(cat.icono) + ' ' : ''}${cat.nombre}`;
            select.appendChild(option);
        });
    }
    
    getIconText(iconClass) {
        // Mapeo básico de iconos Bootstrap a emojis
        const iconMap = {
            'bi-cart-question': '💬',
            'bi-tools': '🛠️',
            'bi-credit-card': '💳',
            'bi-truck': '🚚',
            'bi-shield-check': '⚖️'
        };
        return iconMap[iconClass] || '📌';
    }
    
    /* ================================
       EVENT LISTENERS
       ================================ */
    
    setupEventListeners() {
        // Botón flotante
        const floatingBtn = document.getElementById('chat-floating-button');
        if (floatingBtn) {
            floatingBtn.addEventListener('click', () => this.openChat());
        }
        
        // Cerrar modal
        const closeBtn = document.getElementById('chat-close-btn');
        if (closeBtn) {
            closeBtn.addEventListener('click', () => this.closeChat());
        }
        
        // Overlay click
        const overlay = document.getElementById('chat-modal-overlay');
        if (overlay) {
            overlay.addEventListener('click', (e) => {
                if (e.target === overlay) this.closeChat();
            });
        }
        
        // Botones de acción en vista inicial
        const newConvBtn = document.getElementById('chat-new-conversation-btn');
        if (newConvBtn) {
            newConvBtn.addEventListener('click', () => this.showNewConversationForm());
        }
        
        const viewConvBtn = document.getElementById('chat-view-conversations-btn');
        if (viewConvBtn) {
            viewConvBtn.addEventListener('click', () => this.showConversationsList());
        }
        
        // Botón de volver
        const backBtns = document.querySelectorAll('.chat-back-btn');
        backBtns.forEach(btn => {
            btn.addEventListener('click', () => this.showStartView());
        });
        
        // Formulario de nueva conversación
        const newConvForm = document.getElementById('chat-new-conversation-form');
        if (newConvForm) {
            newConvForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.createConversation();
            });
        }
        
        // Enviar mensaje
        const sendBtn = document.getElementById('chat-send-btn');
        if (sendBtn) {
            sendBtn.addEventListener('click', () => this.sendMessage());
        }
        
        const messageInput = document.getElementById('chat-message-input');
        if (messageInput) {
            messageInput.addEventListener('keypress', (e) => {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    this.sendMessage();
                }
            });
        }
        
        // Cargar pedidos cuando se selecciona categoría post-venta
        const categorySelect = document.getElementById('chat-category-select');
        if (categorySelect) {
            categorySelect.addEventListener('change', (e) => {
                const selectedCatId = parseInt(e.target.value);
                // Categoría 2 es "Soporte post-venta"
                if (selectedCatId === 2) {
                    this.loadUserOrders();
                } else {
                    const orderGroup = document.getElementById('chat-order-group');
                    if (orderGroup) orderGroup.style.display = 'none';
                }
            });
        }
    }
    
    /* ================================
       NAVEGACIÓN DE VISTAS
       ================================ */
    
    showView(viewName) {
        // Ocultar todas las vistas
        const views = document.querySelectorAll('.chat-view');
        views.forEach(view => view.classList.remove('active'));
        
        // Mostrar vista solicitada
        const targetView = document.getElementById(`chat-${viewName}-view`);
        if (targetView) {
            targetView.classList.add('active');
            this.currentView = viewName;
        }
        
        // Gestionar visibilidad del botón de "volver" (antes cerrar)
        const closeBtn = document.getElementById('chat-close-btn');
        if (closeBtn) {
            if (viewName === 'start') {
                closeBtn.style.setProperty('display', 'none', 'important');
            } else {
                closeBtn.style.setProperty('display', 'flex', 'important');
            }
        }
    }
    
    showStartView() {
        this.showView('start');
        this.stopPolling();
    }
    
    showNewConversationForm() {
        this.showView('new-conversation');
    }
    
    async showConversationsList() {
        this.showView('conversations');
        await this.loadConversations();
    }
    
    showActiveConversation(conversationId) {
        this.currentConversationId = conversationId;
        this.showView('active');
        this.loadMessages();
        this.startPolling();
    }
    
    /* ================================
       GESTIÓN DE CONVERSACIONES
       ================================ */
    
    async loadConversations() {
        const container = document.getElementById('chat-conversations-container');
        if (!container) return;
        
        container.innerHTML = '<div class="chat-loading"><div class="chat-spinner"></div>Cargando conversaciones...</div>';
        
        try {
            const response = await fetch('api/chat/obtener_conversaciones.php');
            const data = await response.json();
            
            if (data.success) {
                this.conversations = data.conversaciones;
                this.renderConversations();
            } else {
                container.innerHTML = '<div class="chat-empty-state"><i class="bi bi-chat-dots"></i><p>No tienes conversaciones aún</p></div>';
            }
        } catch (error) {
            console.error('Error loading conversations:', error);
            container.innerHTML = '<div class="chat-empty-state"><i class="bi bi-exclamation-circle"></i><p>Error al cargar conversaciones</p></div>';
        }
    }
    
    renderConversations() {
        const container = document.getElementById('chat-conversations-container');
        if (!container) return;
        
        if (this.conversations.length === 0) {
            container.innerHTML = '<div class="chat-empty-state"><i class="bi bi-chat-dots"></i><p>No tienes conversaciones aún</p></div>';
            return;
        }
        
        container.innerHTML = '';
        
        this.conversations.forEach(conv => {
            const item = document.createElement('div');
            item.className = 'chat-conversation-item';
            item.innerHTML = `
                <div class="chat-conversation-header">
                    <h4 class="chat-conversation-title">
                        ${this.getIconText(conv.categoria.icono)} ${conv.asunto}
                    </h4>
                    <div class="d-flex align-items-center gap-2">
                        ${conv.mensajes_sin_leer > 0 ? `<span class="chat-notification-badge position-relative" style="top: 0; right: 0;">${conv.mensajes_sin_leer}</span>` : ''}
                        <span class="chat-conversation-badge ${conv.estado}">${this.getEstadoText(conv.estado)}</span>
                    </div>
                </div>
                <p class="chat-conversation-preview">${conv.ultimo_mensaje || 'Sin mensajes'}</p>
                <div class="chat-conversation-meta">
                    <span><i class="bi bi-clock"></i> ${this.formatDate(conv.ultimo_mensaje_en)}</span>
                </div>
            `;
            
            item.addEventListener('click', () => this.showActiveConversation(conv.id));
            container.appendChild(item);
        });
    }
    
    async createConversation() {
        const categoryId = document.getElementById('chat-category-select').value;
        const asunto = document.getElementById('chat-subject-input').value.trim();
        const mensaje = document.getElementById('chat-initial-message').value.trim();
        const orderId = document.getElementById('chat-order-select')?.value || null;
        
        if (!categoryId || !asunto || !mensaje) {
            Notiflix.Notify.warning('Por favor completa todos los campos requeridos');
            return;
        }
        
        const submitBtn = document.querySelector('#chat-new-conversation-form button[type="submit"]');
        submitBtn.disabled = true;
        submitBtn.textContent = 'Enviando...';
        
        try {
            const response = await fetch('api/chat/crear_conversacion.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    categoria_id: parseInt(categoryId),
                    asunto: asunto,
                    mensaje_inicial: mensaje,
                    pedido_id: orderId ? parseInt(orderId) : null
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                // Limpiar formulario
                document.getElementById('chat-new-conversation-form').reset();
                
                // Establecer conversación activa
                this.currentConversationId = data.conversacion_id;
                this.showView('active');
                
                // Limpiar contenedor de mensajes
                const container = document.getElementById('chat-messages');
                container.innerHTML = '';
                
                // Agregar mensaje inicial del usuario
                const userMsg = {
                    id: data.mensaje_id,
                    es_admin: false,
                    mensaje: mensaje,
                    leido: false,
                    creado_en: new Date().toISOString()
                };
                this.addMessageToUI(userMsg);
                this.lastMessageId = data.mensaje_id;
                
                // Si hay mensaje automático, agregarlo
                if (data.mensaje_automatico) {
                    this.addMessageToUI(data.mensaje_automatico);
                    this.lastMessageId = data.mensaje_automatico.id;
                }
                
                this.scrollToBottom();
                
                // Iniciar polling para nuevos mensajes
                this.startPolling();
            } else {
                alert(data.message || 'Error al crear conversación');
            }
        } catch (error) {
            console.error('Error creating conversation:', error);
            Notiflix.Notify.failure('Error al crear conversación');
        } finally {
            submitBtn.disabled = false;
            submitBtn.textContent = 'Iniciar Conversación';
        }
    }
    
    /* ================================
       GESTIÓN DE MENSAJES
       ================================ */
    
    async loadMessages() {
        if (!this.currentConversationId) return;
        
        const container = document.getElementById('chat-messages');
        container.innerHTML = '<div class="chat-loading"><div class="chat-spinner"></div>Cargando mensajes...</div>';
        
        try {
            const response = await fetch(`api/chat/obtener_mensajes.php?conversacion_id=${this.currentConversationId}`);
            const data = await response.json();
            
            if (data.success) {
                this.renderMessages(data.mensajes);
                if (data.mensajes.length > 0) {
                    this.lastMessageId = data.mensajes[data.mensajes.length - 1].id;
                }
            } else {
                container.innerHTML = '<div class="chat-empty-state"><p>Error al cargar mensajes</p></div>';
            }
        } catch (error) {
            console.error('Error loading messages:', error);
            container.innerHTML = '<div class="chat-empty-state"><p>Error al cargar mensajes</p></div>';
        }
    }
    
    renderMessages(messages) {
        const container = document.getElementById('chat-messages');
        
        if (messages.length === 0) {
            container.innerHTML = '<div class="chat-empty-state"><i class="bi bi-chat-text"></i><p>Inicia la conversación</p></div>';
            return;
        }
        
        container.innerHTML = '';
        
        messages.forEach(msg => {
            this.addMessageToUI(msg);
        });
        
        this.scrollToBottom();
    }
    
    addMessageToUI(message) {
        if (!message || !message.id) return;
        
        // Verificar si el mensaje ya existe en el DOM
        if (document.querySelector(`[data-message-id="${message.id}"]`)) {
            return;
        }

        const container = document.getElementById('chat-messages');
        const messageEl = document.createElement('div');
        messageEl.className = `chat-message ${message.es_admin ? 'admin' : 'user'}`;
        messageEl.setAttribute('data-message-id', message.id);
        
        const statusIcon = this.getMessageStatusIcon(message);
        
        messageEl.innerHTML = `
            <div class="chat-message-bubble">
                <p class="chat-message-text">${this.escapeHtml(message.mensaje)}</p>
                <div class="chat-message-time">
                    ${this.formatTime(message.creado_en)}
                    ${!message.es_admin ? `<span class="chat-message-status">${statusIcon}</span>` : ''}
                </div>
            </div>
        `;
        
        container.appendChild(messageEl);
    }
    
    getMessageStatusIcon(message) {
        if (message.leido) {
            return '<i class="bi bi-check-all" style="color: #10b981;"></i>';
        } else {
            return '<i class="bi bi-check2"></i>';
        }
    }
    
    async sendMessage() {
        const input = document.getElementById('chat-message-input');
        const mensaje = input.value.trim();
        
        if (!mensaje || !this.currentConversationId) return;
        
        const sendBtn = document.getElementById('chat-send-btn');
        sendBtn.disabled = true;
        
        try {
            const response = await fetch('api/chat/enviar_mensaje.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    conversacion_id: this.currentConversationId,
                    mensaje: mensaje
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                // Agregar mensaje a la UI
                const newMsg = {
                    id: data.mensaje_id,
                    es_admin: false,
                    mensaje: mensaje,
                    leido: false,
                    creado_en: data.creado_en
                };
                
                this.addMessageToUI(newMsg);
                this.lastMessageId = data.mensaje_id;
                
                // Limpiar input
                input.value = '';
                input.style.height = 'auto';
                
                this.scrollToBottom();
            } else {
                alert(data.message || 'Error al enviar mensaje');
            }
        } catch (error) {
            console.error('Error sending message:', error);
            Notiflix.Notify.failure('Error al enviar mensaje');
        } finally {
            sendBtn.disabled = false;
        }
    }
    
    /* ================================
       POLLING PARA TIEMPO REAL
       ================================ */
    
    startPolling() {
        this.stopPolling(); // Detener polling previo si existe
        
        this.poll(); // Primera llamada inmediata
    }
    
    stopPolling() {
        if (this.pollingInterval) {
            clearTimeout(this.pollingInterval);
            this.pollingInterval = null;
        }
    }
    
    async poll() {
        if (!this.currentConversationId || this.currentView !== 'active') {
            return;
        }
        
        try {
            const response = await fetch(
                `api/chat/poll_nuevos_mensajes.php?conversacion_id=${this.currentConversationId}&ultimo_mensaje_id=${this.lastMessageId}`
            );
            
            const data = await response.json();
            
            if (data.success && data.nuevos_mensajes && data.mensajes.length > 0) {
                data.mensajes.forEach(msg => {
                    this.addMessageToUI(msg);
                    this.lastMessageId = msg.id;
                });
                
                this.scrollToBottom();
            }
        } catch (error) {
            console.error('Polling error:', error);
        }
        
        // Programar siguiente poll
        this.pollingInterval = setTimeout(() => this.poll(), 1000);
    }
    
    /* ================================
       FUNCIONES AUXILIARES
       ================================ */
    
    async loadUserOrders() {
        try {
            const response = await fetch('api/chat/obtener_pedidos.php');
            const data = await response.json();
            
            if (data.success) {
                const orderGroup = document.getElementById('chat-order-group');
                const orderSelect = document.getElementById('chat-order-select');
                
                if (orderGroup && orderSelect) {
                    orderGroup.style.display = 'block';
                    orderSelect.innerHTML = '<option value="">Sin asociar a pedido</option>';
                    
                    data.pedidos.forEach(order => {
                        const option = document.createElement('option');
                        option.value = order.id;
                        option.textContent = `Pedido #${order.id} - $${order.valor} (${order.estatus})`;
                        orderSelect.appendChild(option);
                    });
                }
            }
        } catch (error) {
            console.error('Error loading orders:', error);
        }
    }
    
    async updateUnreadBadge() {
        try {
            const response = await fetch('api/chat/obtener_conversaciones.php?estado=abierta');
            const data = await response.json();
            
            if (data.success) {
                let totalUnread = 0;
                data.conversaciones.forEach(conv => {
                    totalUnread += conv.mensajes_sin_leer || 0;
                });
                
                const badge = document.getElementById('chat-notification-count');
                if (badge) {
                    if (totalUnread > 0) {
                        badge.textContent = totalUnread;
                        badge.style.display = 'block';
                    } else {
                        badge.style.display = 'none';
                    }
                }
            }
        } catch (error) {
            console.error('Error updating badge:', error);
        }
    }
    
    openChat() {
        const overlay = document.getElementById('chat-modal-overlay');
        if (overlay) {
            overlay.classList.add('active');
        }
    }
    
    closeChat() {
        // En la interfaz independiente, "cerrar" significa volver a la vista inicial del chat
        this.showStartView();
    }
    
    scrollToBottom() {
        const container = document.getElementById('chat-messages');
        if (container) {
            setTimeout(() => {
                container.scrollTop = container.scrollHeight;
            }, 100);
        }
    }
    
    formatDate(dateString) {
        if (!dateString) return '';
        const date = new Date(dateString);
        const now = new Date();
        const diffMs = now - date;
        const diffMins = Math.floor(diffMs / 60000);
        
        if (diffMins < 1) return 'Ahora';
        if (diffMins < 60) return `Hace ${diffMins}m`;
        if (diffMins < 1440) return `Hace ${Math.floor(diffMins / 60)}h`;
        
        return date.toLocaleDateString('es-ES', { day: 'numeric', month: 'short' });
    }
    
    formatTime(dateString) {
        if (!dateString) return '';
        const date = new Date(dateString);
        return date.toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' });
    }
    
    getEstadoText(estado) {
        const estados = {
            'abierta': 'Abierta',
            'cerrada': 'Cerrada',
            'resuelta': 'Resuelta'
        };
        return estados[estado] || estado;
    }
    
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}

// Inicializar el sistema de chat cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', () => {
    // Verificar si el usuario está logueado antes de inicializar
    const userLoggedIn = document.body.dataset.userLoggedIn === 'true';
    
    if (userLoggedIn) {
        window.chatSystem = new ChatSystem();
        
        // Actualizar badge cada 30 segundos
        setInterval(() => {
            if (window.chatSystem) {
                window.chatSystem.updateUnreadBadge();
            }
        }, 30000);
    }
});
