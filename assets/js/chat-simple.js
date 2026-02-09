/**
 * Lightweight Chat Button Script - iSeller Store
 * Only handles unread message count and UI for the simple floating button
 */

class SimpleChatButton {
    constructor() {
        this.badgeId = 'chat-simple-notification-count';
        this.init();
    }

    async init() {
        // Initial check
        await this.updateUnreadBadge();
        
        // Update badge every 40 seconds (slightly longer than full chat to save resources)
        setInterval(() => this.updateUnreadBadge(), 40000);
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
                
                const badge = document.getElementById(this.badgeId);
                if (badge) {
                    if (totalUnread > 0) {
                        badge.textContent = totalUnread;
                        badge.style.display = 'flex';
                    } else {
                        badge.style.display = 'none';
                    }
                }
            }
        } catch (error) {
            // Silently fail to not disturb UI
            console.warn('SimpleChat: Error updating badge');
        }
    }
}

document.addEventListener('DOMContentLoaded', () => {
    // Check if user is logged in via data attribute on body
    const userLoggedIn = document.body.dataset.userLoggedIn === 'true';
    if (userLoggedIn) {
        new SimpleChatButton();
    }
});
