-- ============================================================
-- MIGRACIÓN: Sistema de Chat Integrado para iSeller Store
-- Fecha: 2026-02-04
-- Descripción: Crea las tablas necesarias para el canal de
--              comunicación integrado entre usuarios y tienda
-- ============================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- ============================================================
-- 1. Tabla de Categorías de Contacto
-- ============================================================

CREATE TABLE IF NOT EXISTS `chat_categorias` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `icono` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'bi-chat-dots',
  `orden` int(11) NOT NULL DEFAULT 0,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `creado_en` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertar categorías predefinidas
INSERT INTO `chat_categorias` (`nombre`, `descripcion`, `icono`, `orden`, `activo`) VALUES
('Consultas antes de comprar', 'Preguntas sobre productos, disponibilidad, características y precios', 'bi-cart-question', 1, 1),
('Soporte post-venta', 'Asistencia después de realizar una compra', 'bi-tools', 2, 1),
('Pagos y facturación', 'Consultas sobre métodos de pago, facturas y transacciones', 'bi-credit-card', 3, 1),
('Envíos y devoluciones', 'Seguimiento de envíos, tiempos de entrega y devoluciones', 'bi-truck', 4, 1),
('Garantías y reclamos', 'Información sobre garantías y gestión de reclamos', 'bi-shield-check', 5, 1);

-- ============================================================
-- 2. Tabla de Conversaciones
-- ============================================================

CREATE TABLE IF NOT EXISTS `chat_conversaciones` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) NOT NULL,
  `pedido_id` int(11) DEFAULT NULL,
  `categoria_id` int(11) NOT NULL,
  `asunto` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `estado` enum('abierta','cerrada','resuelta') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'abierta',
  `ultimo_mensaje_en` datetime DEFAULT NULL,
  `creada_en` datetime DEFAULT current_timestamp(),
  `actualizada_en` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `cerrada_en` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_usuario` (`usuario_id`),
  KEY `idx_pedido` (`pedido_id`),
  KEY `idx_categoria` (`categoria_id`),
  KEY `idx_estado` (`estado`),
  KEY `idx_ultimo_mensaje` (`ultimo_mensaje_en`),
  CONSTRAINT `fk_chat_conv_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_chat_conv_categoria` FOREIGN KEY (`categoria_id`) REFERENCES `chat_categorias` (`id`),
  CONSTRAINT `fk_chat_conv_pedido` FOREIGN KEY (`pedido_id`) REFERENCES `compras_por_usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 3. Tabla de Mensajes
-- ============================================================

CREATE TABLE IF NOT EXISTS `chat_mensajes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `conversacion_id` int(11) NOT NULL,
  `es_admin` tinyint(1) NOT NULL DEFAULT 0,
  `admin_id` int(11) DEFAULT NULL,
  `mensaje` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `leido` tinyint(1) NOT NULL DEFAULT 0,
  `leido_en` datetime DEFAULT NULL,
  `creado_en` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_conversacion` (`conversacion_id`),
  KEY `idx_es_admin` (`es_admin`),
  KEY `idx_leido` (`leido`),
  KEY `idx_creado` (`creado_en`),
  CONSTRAINT `fk_chat_msg_conversacion` FOREIGN KEY (`conversacion_id`) REFERENCES `chat_conversaciones` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_chat_msg_admin` FOREIGN KEY (`admin_id`) REFERENCES `administradores` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 4. Tabla de Configuración de Tienda
-- ============================================================

CREATE TABLE IF NOT EXISTS `tienda_configuracion` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre_comercial` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'iSeller Store',
  `logo_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `horario_atencion` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT 'Lun-Vie 9:00 AM - 6:00 PM',
  `tiempo_respuesta_estimado` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT '~2 horas',
  `telefono` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `direccion` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mensaje_bienvenida` text COLLATE utf8mb4_unicode_ci DEFAULT '¡Hola! 👋 Bienvenido a nuestro canal de soporte. ¿En qué podemos ayudarte hoy?',
  `mensaje_fuera_horario` text COLLATE utf8mb4_unicode_ci DEFAULT 'Gracias por contactarnos. Actualmente estamos fuera de horario de atención. Te responderemos pronto.',
  `chat_activo` tinyint(1) NOT NULL DEFAULT 1,
  `actualizado_en` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertar configuración por defecto
INSERT INTO `tienda_configuracion` 
(`nombre_comercial`, `horario_atencion`, `tiempo_respuesta_estimado`, `email`, `chat_activo`) 
VALUES 
('iSeller Store', 'Lun-Vie 9:00 AM - 6:00 PM', '~2 horas', 'soporte@iseller.com', 1);

-- ============================================================
-- 5. Tabla de Mensajes Automáticos
-- ============================================================

CREATE TABLE IF NOT EXISTS `chat_mensajes_automaticos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tipo` enum('bienvenida','primera_respuesta','seguimiento','cierre') COLLATE utf8mb4_unicode_ci NOT NULL,
  `categoria_id` int(11) DEFAULT NULL,
  `mensaje` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `creado_en` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_tipo` (`tipo`),
  KEY `fk_msg_auto_categoria` (`categoria_id`),
  CONSTRAINT `fk_msg_auto_categoria` FOREIGN KEY (`categoria_id`) REFERENCES `chat_categorias` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Mensajes automáticos predefinidos
INSERT INTO `chat_mensajes_automaticos` (`tipo`, `categoria_id`, `mensaje`, `activo`) VALUES
('bienvenida', NULL, '¡Hola! 👋 Gracias por contactarnos. Un miembro de nuestro equipo te responderá pronto.', 1),
('primera_respuesta', 1, 'Gracias por tu consulta. Nuestro equipo de ventas está revisando tu pregunta y te responderá en breve.', 1),
('primera_respuesta', 2, 'Hemos recibido tu solicitud de soporte. Nuestro equipo técnico está trabajando en tu caso.', 1),
('primera_respuesta', 3, 'Tu consulta sobre pagos ha sido recibida. Verificaremos la información y te responderemos pronto.', 1),
('primera_respuesta', 4, 'Gracias por contactarnos sobre tu envío. Revisaremos el estado y te actualizaremos.', 1),
('primera_respuesta', 5, 'Tu reclamo es importante para nosotros. Nuestro equipo lo está revisando con atención.', 1);

COMMIT;

-- ============================================================
-- FIN DE MIGRACIÓN
-- ============================================================
