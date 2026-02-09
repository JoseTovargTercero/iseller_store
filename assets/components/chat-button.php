<?php if (isLoggedIn()): ?>
    <!-- Botón de Chat Simple que redirige a la interfaz independiente -->
    <a href="soporte.php" class="chat-floating-button" aria-label="Ir a soporte" style="text-decoration: none;">
        <i class="bi bi-chat-dots-fill"></i>
        <span id="chat-simple-notification-count" class="chat-notification-badge" style="display: none; align-items: center; justify-content: center;">0</span>
    </a>
<?php endif; ?>
