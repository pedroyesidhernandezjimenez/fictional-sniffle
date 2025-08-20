-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 20-08-2025 a las 00:59:40
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
-- Base de datos: `tickets_db`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tickets`
--

CREATE TABLE `tickets` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `tecnico_id` int(11) DEFAULT NULL,
  `nombre_dependencia` varchar(100) DEFAULT NULL,
  `ubicacion` varchar(100) DEFAULT NULL,
  `equipo` varchar(100) DEFAULT NULL,
  `descripcion` text DEFAULT NULL,
  `marca_modelo` varchar(100) DEFAULT NULL,
  `numero_inventario` varchar(50) DEFAULT NULL,
  `solucion` text DEFAULT NULL,
  `estado` enum('En espera','En proceso','Solucionado') DEFAULT 'En espera',
  `firmado` tinyint(1) DEFAULT 0,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_terminacion` datetime DEFAULT NULL,
  `fecha_firmado` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tickets_profesor`
--

CREATE TABLE `tickets_profesor` (
  `id` int(11) NOT NULL,
  `profesor_id` int(11) NOT NULL,
  `tipo_ticket` enum('requerimiento','danio') NOT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `estado` enum('En espera','En proceso','Solucionado') DEFAULT 'En espera',
  `programa` varchar(150) DEFAULT NULL,
  `sala_requerimiento` varchar(100) DEFAULT NULL,
  `fecha_requerida` date DEFAULT NULL,
  `descripcion_actividad` text DEFAULT NULL,
  `sala_danio` varchar(100) DEFAULT NULL,
  `salon` varchar(100) DEFAULT NULL,
  `descripcion_problema` text DEFAULT NULL,
  `tecnico_id` int(11) DEFAULT NULL,
  `firmado` tinyint(1) DEFAULT 0,
  `fecha_firmado` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `correo` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('solicitante','tecnico','admin','profesor','beca_trabajo') NOT NULL,
  `nombre_completo` varchar(100) NOT NULL,
  `cargo` varchar(50) NOT NULL,
  `online` tinyint(1) DEFAULT 0,
  `ultimo_login` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `tickets`
--
ALTER TABLE `tickets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `tecnico_id` (`tecnico_id`);

--
-- Indices de la tabla `tickets_profesor`
--
ALTER TABLE `tickets_profesor`
  ADD PRIMARY KEY (`id`),
  ADD KEY `profesor_id` (`profesor_id`),
  ADD KEY `tecnico_id` (`tecnico_id`);

--
-- Indices de la tabla `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `correo` (`correo`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `tickets`
--
ALTER TABLE `tickets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `tickets_profesor`
--
ALTER TABLE `tickets_profesor`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `tickets`
--
ALTER TABLE `tickets`
  ADD CONSTRAINT `tickets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `tickets_ibfk_2` FOREIGN KEY (`tecnico_id`) REFERENCES `users` (`id`);

--
-- Filtros para la tabla `tickets_profesor`
--
ALTER TABLE `tickets_profesor`
  ADD CONSTRAINT `tickets_profesor_ibfk_1` FOREIGN KEY (`profesor_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `tickets_profesor_ibfk_2` FOREIGN KEY (`tecnico_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
