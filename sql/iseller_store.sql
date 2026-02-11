-- phpMyAdmin SQL Dump
-- version 5.0.2
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 16-01-2026 a las 04:55:08
-- Versión del servidor: 10.4.13-MariaDB
-- Versión de PHP: 7.4.8

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `iseller_store`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `administradores`
--

CREATE TABLE `administradores` (
  `id` int(11) NOT NULL,
  `usuario` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password_hash` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `administradores`
--

INSERT INTO `administradores` (`id`, `usuario`, `password_hash`, `created_at`) VALUES
(1, 'admin', '$2y$10$fTGXTXqsZlwFbBwNOz8fhuYkyK7GE1tiBPNb.zABoSd4TSenHNwT.', '2026-01-15 19:31:36');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `compras_por_usuarios`
--

CREATE TABLE `compras_por_usuarios` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `compra_id` int(11) NOT NULL,
  `direccion_id` int(11) DEFAULT NULL,
  `valor_compra` decimal(12,2) NOT NULL,
  `valor_compra_bs` decimal(12,2) NOT NULL DEFAULT 0.00,
  `numero_operacion_bancaria` varchar(50) NOT NULL,
  `hora_operacion_bancaria` varchar(50) NOT NULL,
  `ganancia_generada` decimal(12,2) NOT NULL,
  `fecha` datetime DEFAULT current_timestamp(),
  `tipo_entrega` varchar(100) NOT NULL,
  `importe_envio` decimal(10,2) NOT NULL DEFAULT 0.00,
  `estatus` enum('pendiente','en_revision','enviada','entregada','rechazada') NOT NULL,
  `puntos_generados` decimal(5,2) DEFAULT 0.00,
  `nivel_resultante` int(11) DEFAULT NULL,
  `ahorrado` decimal(12,2) NOT NULL DEFAULT 0.00,
  `ahorrado_bs` decimal(12,2) NOT NULL DEFAULT 0.00,
  `estado` enum('pendiente','en_revision','enviada','entregada','rechazada') DEFAULT 'pendiente',
  `motivo_rechazo` text DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `historial_puntos`
--

CREATE TABLE `historial_puntos` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `compra_id` int(11) DEFAULT NULL,
  `puntos_generados` decimal(5,2) NOT NULL,
  `ganancia_base` decimal(10,2) NOT NULL,
  `puntos_aplicados` decimal(5,2) NOT NULL,
  `fecha` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `orden`
--

CREATE TABLE `orden` (
  `id` int(11) NOT NULL,
  `status` int(11) DEFAULT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `total_price` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `created` datetime DEFAULT NULL,
  `modified` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `fecha` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `semana` varchar(10) COLLATE utf8_unicode_ci DEFAULT NULL,
  `ano` varchar(10) COLLATE utf8_unicode_ci DEFAULT NULL,
  `total_price_bs` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `total_price_cop` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `cliente` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `tipoPago` int(11) DEFAULT NULL,
  `dia` varchar(10) COLLATE utf8_unicode_ci DEFAULT NULL,
  `descontado` varchar(10) COLLATE utf8_unicode_ci DEFAULT NULL,
  `id_sucursal` int(11) DEFAULT NULL,
  `bss_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `orden_articulos`
--

CREATE TABLE `orden_articulos` (
  `id` int(11) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `quantity` varchar(5) COLLATE utf8_unicode_ci DEFAULT NULL,
  `precio` varchar(10) COLLATE utf8_unicode_ci DEFAULT NULL,
  `bolivar` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `peso` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `precio_venta_dolar` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `precio_venta_bs` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `precio_venta_cop` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `id_sucursal` int(11) NOT NULL,
  `bss_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `recompensas_usuario`
--

CREATE TABLE `recompensas_usuario` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `nivel_desbloqueo` int(11) NOT NULL,
  `tipo` enum('monetaria','descuento_ganancia','','') NOT NULL,
  `monto` decimal(10,2) NOT NULL,
  `estado` enum('bloqueado','disponible','usado','expirado') DEFAULT 'bloqueado',
  `fecha_creacion` datetime DEFAULT current_timestamp(),
  `fecha_uso` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) DEFAULT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `estado` tinyint(4) DEFAULT 1,
  `creado_en` datetime DEFAULT current_timestamp(),
  `puntos` decimal(6,2) DEFAULT 0.00,
  `nivel` int(11) DEFAULT 1,
  `reset_token` varchar(255) DEFAULT NULL,
  `reset_expires` datetime DEFAULT NULL,
  `referral_code` varchar(255) DEFAULT NULL,
  `session_id` varchar(255) DEFAULT NULL,
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `nombre`, `email`, `password`, `telefono`, `estado`, `creado_en`, `puntos`, `nivel`, `reset_token`, `reset_expires`) VALUES
(1, 'Ricardo', 'ac.80014.dc@gmail.com', '$2y$10$r9cOVmZiRzDh0He1w8ORPOWUMmQoFWBC7.RuTEObtMUd8KyHKm6Ba', NULL, 1, '2026-01-15 20:54:08', '0.98', 1, NULL, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios_direcciones`
--

CREATE TABLE `usuarios_direcciones` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `nombre_receptor` varchar(100) DEFAULT NULL,
  `telefono` varchar(20) NOT NULL,
  `direccion` text NOT NULL,
  `referencia` text DEFAULT NULL,
  `lat` decimal(10,8) DEFAULT NULL,
  `lng` decimal(11,8) DEFAULT NULL,
  `es_principal` tinyint(4) DEFAULT 1,
  `creado_en` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `administradores`
--
ALTER TABLE `administradores`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `usuario` (`usuario`);

--
-- Indices de la tabla `compras_por_usuarios`
--
ALTER TABLE `compras_por_usuarios`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_usuario` (`usuario_id`),
  ADD KEY `idx_compra` (`compra_id`),
  ADD KEY `fk_cpu_direccion` (`direccion_id`);

--
-- Indices de la tabla `historial_puntos`
--
ALTER TABLE `historial_puntos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Indices de la tabla `orden`
--
ALTER TABLE `orden`
  ADD PRIMARY KEY (`id`),
  ADD KEY `customer_id` (`customer_id`);

--
-- Indices de la tabla `orden_articulos`
--
ALTER TABLE `orden_articulos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`);

--
-- Indices de la tabla `recompensas_usuario`
--
ALTER TABLE `recompensas_usuario`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indices de la tabla `usuarios_direcciones`
--
ALTER TABLE `usuarios_direcciones`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_usuario` (`usuario_id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `administradores`
--
ALTER TABLE `administradores`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `compras_por_usuarios`
--
ALTER TABLE `compras_por_usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `historial_puntos`
--
ALTER TABLE `historial_puntos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `orden`
--
ALTER TABLE `orden`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `orden_articulos`
--
ALTER TABLE `orden_articulos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `recompensas_usuario`
--
ALTER TABLE `recompensas_usuario`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `usuarios_direcciones`
--
ALTER TABLE `usuarios_direcciones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `compras_por_usuarios`
--
ALTER TABLE `compras_por_usuarios`
  ADD CONSTRAINT `fk_cpu_direccion` FOREIGN KEY (`direccion_id`) REFERENCES `usuarios_direcciones` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_cpu_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `historial_puntos`
--
ALTER TABLE `historial_puntos`
  ADD CONSTRAINT `historial_puntos_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `recompensas_usuario`
--
ALTER TABLE `recompensas_usuario`
  ADD CONSTRAINT `recompensas_usuario_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `usuarios_direcciones`
--
ALTER TABLE `usuarios_direcciones`
  ADD CONSTRAINT `fk_ud_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
