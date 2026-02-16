-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: localhost
-- Tiempo de generación: 16-02-2026 a las 16:12:14
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `wirvux`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `mensajes`
--

CREATE TABLE `mensajes` (
  `id` int(11) NOT NULL,
  `id_emisor` int(11) NOT NULL,
  `id_receptor` int(11) NOT NULL,
  `mensaje` text NOT NULL,
  `id_respuesta` int(11) DEFAULT NULL,
  `leido` tinyint(1) DEFAULT 0,
  `fecha_envio` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `mensajes`
--

INSERT INTO `mensajes` (`id`, `id_emisor`, `id_receptor`, `mensaje`, `id_respuesta`, `leido`, `fecha_envio`) VALUES
(1, 4, 3, 'hola Buenas', NULL, 0, '2026-02-11 14:59:44'),
(2, 4, 3, 'aa', 1, 0, '2026-02-11 14:59:51'),
(3, 4, 3, 'gf', 2, 0, '2026-02-11 15:04:52'),
(4, 4, 3, 'sdf', 3, 0, '2026-02-11 15:11:38'),
(5, 4, 3, 'vcggf', NULL, 0, '2026-02-11 15:18:40'),
(6, 4, 3, 'hola', NULL, 0, '2026-02-13 15:18:53'),
(7, 4, 3, 'aaa', 6, 0, '2026-02-13 15:19:05'),
(8, 4, 3, 'ewre', 6, 0, '2026-02-13 21:16:32'),
(9, 4, 3, 'hola', NULL, 0, '2026-02-13 21:22:48'),
(10, 4, 3, 'hola', 9, 0, '2026-02-13 21:23:00');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `propuestas`
--

CREATE TABLE `propuestas` (
  `id` int(11) NOT NULL,
  `id_trabajo` int(11) DEFAULT NULL,
  `id_autonomo` int(11) DEFAULT NULL,
  `mensaje` text DEFAULT NULL,
  `presupuesto_ofrecido` decimal(10,2) DEFAULT NULL,
  `fecha_envio` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_postulacion` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `resenas`
--

CREATE TABLE `resenas` (
  `id` int(11) NOT NULL,
  `id_autonomo` int(11) DEFAULT NULL,
  `id_cliente` int(11) DEFAULT NULL,
  `estrellas` int(11) DEFAULT NULL CHECK (`estrellas` between 1 and 5),
  `comentario` text DEFAULT NULL,
  `fecha` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `solicitudes`
--

CREATE TABLE `solicitudes` (
  `id` int(11) NOT NULL,
  `cliente_id` int(11) DEFAULT NULL,
  `titulo` varchar(100) DEFAULT NULL,
  `descripcion` text DEFAULT NULL,
  `categoria` varchar(50) DEFAULT NULL,
  `fecha_publicacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `trabajos`
--

CREATE TABLE `trabajos` (
  `id` int(11) NOT NULL,
  `titulo` varchar(100) NOT NULL,
  `descripcion` text NOT NULL,
  `categoria` varchar(50) NOT NULL,
  `presupuesto` decimal(10,2) DEFAULT NULL,
  `id_cliente` int(11) DEFAULT NULL,
  `id_autonomo` int(11) DEFAULT NULL,
  `estado` enum('abierto','en_progreso','completado') DEFAULT 'abierto',
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `trabajos`
--

INSERT INTO `trabajos` (`id`, `titulo`, `descripcion`, `categoria`, `presupuesto`, `id_cliente`, `id_autonomo`, `estado`, `fecha_creacion`) VALUES
(5, 'Mantenimiento Servidores 2026', 'Revisión anual de sistemas', 'Sistemas', 450.00, 4, 3, 'en_progreso', '2026-02-10 09:00:00'),
(6, 'Desarrollo App Móvil 2026', 'Proyecto finalizado el año pasado', 'Desarrollo', 1200.00, 4, 3, 'completado', '2026-01-01 11:00:00'),
(7, 'Consultoría Redes 2024', 'Instalación de fibra óptica', 'Redes', 300.00, 4, 3, 'completado', '2024-11-20 08:30:00');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `nombre` varchar(30) DEFAULT NULL,
  `apellidos` varchar(40) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `tipo_usuario` enum('cliente','autonomo') DEFAULT NULL,
  `categoria_principal` varchar(50) DEFAULT NULL,
  `especialidad` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `nombre`, `apellidos`, `email`, `password`, `tipo_usuario`, `categoria_principal`, `especialidad`) VALUES
(3, 'gabriel', 'poveda', 'gpovher1507@gmail.com', '$2y$10$fmGH8Lap5FEYUx/L3xk/8OQq49Vnq0jKT7h8XHQvVF2g8DaGpKenq', 'autonomo', 'Tecnología', 'Sistemas'),
(4, 'gabriel', 'poveda', 'povedagabriel666@gmail.com', '$2y$10$lpmG79Ok8L.A8DDTzAjyweyZ.6zVfJorJAUqQLuukRHEeR72/uDd2', 'cliente', '', '');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `mensajes`
--
ALTER TABLE `mensajes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_emisor` (`id_emisor`),
  ADD KEY `id_receptor` (`id_receptor`),
  ADD KEY `id_respuesta` (`id_respuesta`);

--
-- Indices de la tabla `propuestas`
--
ALTER TABLE `propuestas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_trabajo` (`id_trabajo`),
  ADD KEY `id_autonomo` (`id_autonomo`);

--
-- Indices de la tabla `resenas`
--
ALTER TABLE `resenas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_autonomo` (`id_autonomo`),
  ADD KEY `id_cliente` (`id_cliente`);

--
-- Indices de la tabla `solicitudes`
--
ALTER TABLE `solicitudes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cliente_id` (`cliente_id`);

--
-- Indices de la tabla `trabajos`
--
ALTER TABLE `trabajos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_cliente` (`id_cliente`),
  ADD KEY `idx_reporte` (`id_autonomo`,`estado`,`fecha_creacion`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `mensajes`
--
ALTER TABLE `mensajes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de la tabla `propuestas`
--
ALTER TABLE `propuestas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `resenas`
--
ALTER TABLE `resenas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `solicitudes`
--
ALTER TABLE `solicitudes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `trabajos`
--
ALTER TABLE `trabajos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `mensajes`
--
ALTER TABLE `mensajes`
  ADD CONSTRAINT `mensajes_ibfk_1` FOREIGN KEY (`id_emisor`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `mensajes_ibfk_2` FOREIGN KEY (`id_receptor`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `mensajes_ibfk_3` FOREIGN KEY (`id_respuesta`) REFERENCES `mensajes` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `propuestas`
--
ALTER TABLE `propuestas`
  ADD CONSTRAINT `propuestas_ibfk_1` FOREIGN KEY (`id_trabajo`) REFERENCES `trabajos` (`id`),
  ADD CONSTRAINT `propuestas_ibfk_2` FOREIGN KEY (`id_autonomo`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `resenas`
--
ALTER TABLE `resenas`
  ADD CONSTRAINT `resenas_ibfk_1` FOREIGN KEY (`id_autonomo`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `resenas_ibfk_2` FOREIGN KEY (`id_cliente`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `solicitudes`
--
ALTER TABLE `solicitudes`
  ADD CONSTRAINT `solicitudes_ibfk_1` FOREIGN KEY (`cliente_id`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `trabajos`
--
ALTER TABLE `trabajos`
  ADD CONSTRAINT `trabajos_ibfk_1` FOREIGN KEY (`id_cliente`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `trabajos_ibfk_2` FOREIGN KEY (`id_autonomo`) REFERENCES `usuarios` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
