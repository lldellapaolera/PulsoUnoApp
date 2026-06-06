-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 06-06-2026 a las 22:30:26
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `pulsounoappdb`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `asistencias`
--

CREATE TABLE `asistencias` (
  `id` int(11) NOT NULL,
  `comision_id` int(11) NOT NULL,
  `alumno_id` int(11) NOT NULL,
  `fecha` date NOT NULL,
  `presente` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `asistencias`
--

INSERT INTO `asistencias` (`id`, `comision_id`, `alumno_id`, `fecha`, `presente`) VALUES
(1, 12, 2, '2026-06-06', 1),
(2, 12, 3, '2026-06-06', 1),
(3, 12, 4, '2026-06-06', 1),
(4, 12, 5, '2026-06-06', 1),
(5, 12, 2, '2026-06-28', 1),
(6, 12, 3, '2026-06-28', 1),
(7, 12, 4, '2026-06-28', 1),
(8, 12, 5, '2026-06-28', 1),
(9, 12, 2, '2026-05-14', 1),
(10, 12, 3, '2026-05-14', 1),
(11, 12, 4, '2026-05-14', 1),
(12, 12, 5, '2026-05-14', 1),
(13, 12, 2, '2026-05-07', 1),
(14, 12, 3, '2026-05-07', 1),
(15, 12, 4, '2026-05-07', 1),
(16, 12, 5, '2026-05-07', 1),
(17, 12, 2, '2026-04-30', 1),
(18, 12, 3, '2026-04-30', 1),
(19, 12, 4, '2026-04-30', 1),
(20, 12, 5, '2026-04-30', 0),
(21, 12, 2, '2026-04-23', 1),
(22, 12, 3, '2026-04-23', 1),
(23, 12, 4, '2026-04-23', 1),
(24, 12, 5, '2026-04-23', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `comisiones`
--

CREATE TABLE `comisiones` (
  `id` int(11) NOT NULL,
  `materia_id` int(11) NOT NULL,
  `profesor_id` int(11) NOT NULL,
  `cuatrimestre` tinyint(4) NOT NULL,
  `anio` int(11) NOT NULL,
  `dias_horarios` varchar(150) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `comisiones`
--

INSERT INTO `comisiones` (`id`, `materia_id`, `profesor_id`, `cuatrimestre`, `anio`, `dias_horarios`) VALUES
(12, 2, 1, 1, 2026, 'Lunes y Viernes de 18:00 a 22:00 hs');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `correlatividades`
--

CREATE TABLE `correlatividades` (
  `id` int(11) NOT NULL,
  `materia_id` int(11) NOT NULL,
  `correlativa_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `correlatividades`
--

INSERT INTO `correlatividades` (`id`, `materia_id`, `correlativa_id`) VALUES
(1, 3, 1),
(2, 4, 2);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `inscripciones`
--

CREATE TABLE `inscripciones` (
  `id` int(11) NOT NULL,
  `comision_id` int(11) NOT NULL,
  `alumno_id` int(11) NOT NULL,
  `nota_parcial1` decimal(4,2) DEFAULT NULL,
  `nota_parcial2` decimal(4,2) DEFAULT NULL,
  `nota_tps` decimal(4,2) DEFAULT NULL,
  `nota_final` decimal(4,2) DEFAULT NULL,
  `estado_materia` enum('cursando','regular','promocionada','libre') NOT NULL DEFAULT 'cursando'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `inscripciones`
--

INSERT INTO `inscripciones` (`id`, `comision_id`, `alumno_id`, `nota_parcial1`, `nota_parcial2`, `nota_tps`, `nota_final`, `estado_materia`) VALUES
(5, 12, 2, NULL, NULL, NULL, NULL, 'cursando'),
(6, 12, 3, NULL, NULL, NULL, NULL, 'cursando'),
(7, 12, 4, NULL, NULL, NULL, NULL, 'cursando'),
(8, 12, 5, NULL, NULL, NULL, NULL, 'cursando');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `materias`
--

CREATE TABLE `materias` (
  `id` int(11) NOT NULL,
  `codigo` varchar(10) NOT NULL,
  `nombre` varchar(150) NOT NULL,
  `promocionable` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `materias`
--

INSERT INTO `materias` (`id`, `codigo`, `nombre`, `promocionable`, `created_at`) VALUES
(1, '07017', 'Bases de Datos II', 1, '2026-06-05 17:10:30'),
(2, '07044', 'Interfaces de Usuario y Tecnologías Web', 1, '2026-06-05 17:10:30'),
(3, '07046', 'Explotación de Datos', 1, '2026-06-05 17:14:58'),
(4, '07060', 'Práctica Profesional', 1, '2026-06-05 17:14:58');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `legajo` varchar(20) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `apellido` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `rol` enum('alumno','profesor','administrador') NOT NULL DEFAULT 'alumno',
  `telefono` varchar(20) DEFAULT NULL,
  `localidad` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `legajo`, `nombre`, `apellido`, `email`, `password`, `rol`, `telefono`, `localidad`, `created_at`, `updated_at`) VALUES
(1, 'PROF-1090', 'Pedro Salvador', 'Occhipinti', 'pocchipinti@uno.edu.ar', '2ff423c415a49d4b37e87a74c5f8de7b', 'profesor', '+54 11 0000-0000', 'Merlo', '2026-06-05 16:30:38', '2026-06-06 15:18:53'),
(2, 'UNO-4597', 'Lucas Lionel', 'Salinas', 'lsalinas@alumno.uno.edu.ar', '$2y$10$7R9Mv6GgGf7xYxZ8qV9eOe7K3C9I2M6vB3vW8R9O3X8eCeKeKeKe.', 'alumno', '+54 9 11 5802-4597', 'San Antonio de Padua', '2026-06-05 16:30:39', '2026-06-05 16:30:39'),
(3, 'UNO-0003', 'Mario Lucas', 'Lastoria', 'mlastoria@alumno.uno.edu.ar', '$2y$10$7R9Mv6GgGf7xYxZ8qV9eOe7K3C9I2M6vB3vW8R9O3X8eCeKeKeKe.', 'alumno', '+54 9 11 6282-0003', 'Ituzaingó', '2026-06-05 16:30:39', '2026-06-05 16:30:39'),
(4, 'UNO-8701', 'Matías Nahuel', 'Castillo', 'mcastillo@alumno.uno.edu.ar', '$2y$10$7R9Mv6GgGf7xYxZ8qV9eOe7K3C9I2M6vB3vW8R9O3X8eCeKeKeKe.', 'alumno', '+54 9 11 6198-8701', 'Castelar', '2026-06-05 16:30:39', '2026-06-05 16:30:39'),
(5, 'UNO-5606', 'León', 'Della Paolera', 'ldellapaolera@alumno.uno.edu.ar', '2ff423c415a49d4b37e87a74c5f8de7b', 'alumno', '+54 9 11 6430-5606', 'Merlo', '2026-06-05 16:30:39', '2026-06-05 19:42:37');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `viajes`
--

CREATE TABLE `viajes` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `tipo_usuario` enum('conductor','acompañante') NOT NULL,
  `origen` varchar(100) NOT NULL,
  `destino` varchar(100) NOT NULL DEFAULT 'Sede UNO',
  `dias_viaje` varchar(100) NOT NULL,
  `hora_salida` time NOT NULL,
  `asientos_disponibles` tinyint(4) DEFAULT 0,
  `detalles` text DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `viajes`
--

INSERT INTO `viajes` (`id`, `usuario_id`, `tipo_usuario`, `origen`, `destino`, `dias_viaje`, `hora_salida`, `asientos_disponibles`, `detalles`, `activo`, `created_at`) VALUES
(1, 5, 'conductor', 'Mariano Acosta (Estación / Av. Libertador)', 'Sede UNO', 'Lunes y Viernes', '18:00:00', 3, 'Salgo de la Estación de Mariano Acosta, voy por Rivadavia recto hasta la sede. Hay mate.', 1, '2026-06-05 17:19:19'),
(2, 4, 'conductor', 'Castelar Sur', 'Sede UNO', 'Viernes', '17:45:00', 2, 'Voy directo por autopista. Tolerancia de espera: 10 minutos.', 1, '2026-06-05 17:19:19'),
(3, 2, 'acompañante', 'San Antonio de Padua', 'Sede UNO', 'Lunes y Viernes', '18:15:00', 0, 'Busco algún compañero que pase cerca de la barrera de Acevedo para compartir gastos de nafta.', 1, '2026-06-05 17:19:19');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `asistencias`
--
ALTER TABLE `asistencias`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `asistencia_unica` (`comision_id`,`alumno_id`,`fecha`),
  ADD KEY `alumno_id` (`alumno_id`);

--
-- Indices de la tabla `comisiones`
--
ALTER TABLE `comisiones`
  ADD PRIMARY KEY (`id`),
  ADD KEY `materia_id` (`materia_id`),
  ADD KEY `profesor_id` (`profesor_id`);

--
-- Indices de la tabla `correlatividades`
--
ALTER TABLE `correlatividades`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unica_correlatividad` (`materia_id`,`correlativa_id`),
  ADD KEY `correlativa_id` (`correlativa_id`);

--
-- Indices de la tabla `inscripciones`
--
ALTER TABLE `inscripciones`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `alumno_comision` (`comision_id`,`alumno_id`),
  ADD KEY `alumno_id` (`alumno_id`);

--
-- Indices de la tabla `materias`
--
ALTER TABLE `materias`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `codigo` (`codigo`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `legajo` (`legajo`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indices de la tabla `viajes`
--
ALTER TABLE `viajes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `asistencias`
--
ALTER TABLE `asistencias`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT de la tabla `comisiones`
--
ALTER TABLE `comisiones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT de la tabla `correlatividades`
--
ALTER TABLE `correlatividades`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `inscripciones`
--
ALTER TABLE `inscripciones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de la tabla `materias`
--
ALTER TABLE `materias`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `viajes`
--
ALTER TABLE `viajes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `asistencias`
--
ALTER TABLE `asistencias`
  ADD CONSTRAINT `asistencias_ibfk_1` FOREIGN KEY (`comision_id`) REFERENCES `comisiones` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `asistencias_ibfk_2` FOREIGN KEY (`alumno_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `comisiones`
--
ALTER TABLE `comisiones`
  ADD CONSTRAINT `comisiones_ibfk_1` FOREIGN KEY (`materia_id`) REFERENCES `materias` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `comisiones_ibfk_2` FOREIGN KEY (`profesor_id`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `correlatividades`
--
ALTER TABLE `correlatividades`
  ADD CONSTRAINT `correlatividades_ibfk_1` FOREIGN KEY (`materia_id`) REFERENCES `materias` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `correlatividades_ibfk_2` FOREIGN KEY (`correlativa_id`) REFERENCES `materias` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `inscripciones`
--
ALTER TABLE `inscripciones`
  ADD CONSTRAINT `inscripciones_ibfk_1` FOREIGN KEY (`comision_id`) REFERENCES `comisiones` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `inscripciones_ibfk_2` FOREIGN KEY (`alumno_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `viajes`
--
ALTER TABLE `viajes`
  ADD CONSTRAINT `viajes_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
