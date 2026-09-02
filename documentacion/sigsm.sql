-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 02-09-2026 a las 22:25:56
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
-- Base de datos: `sigsm`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `canal_solicitud`
--

CREATE TABLE `canal_solicitud` (
  `id_canal` int(11) NOT NULL,
  `nombre` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `canal_solicitud`
--

INSERT INTO `canal_solicitud` (`id_canal`, `nombre`) VALUES
(2, 'Correo'),
(1, 'Gestión Salud'),
(3, 'Papel');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `categoria`
--

CREATE TABLE `categoria` (
  `id_categoria` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `codigo_qr` varchar(255) NOT NULL,
  `tipo_categoria` enum('documental','encuesta') NOT NULL DEFAULT 'documental'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `categoria`
--

INSERT INTO `categoria` (`id_categoria`, `nombre`, `descripcion`, `codigo_qr`, `tipo_categoria`) VALUES
(1, 'Urología', NULL, 'QR-UROLOGIA', 'documental'),
(2, 'Cardiología', NULL, 'QR-CARDIOLOGIA', 'documental'),
(3, 'Traumatología', NULL, 'QR-TRAUMATOLOGIA', 'documental'),
(4, 'Gastroenterología', NULL, 'QR-GASTROENTEROLOGIA', 'documental'),
(5, 'Ginecobstetricia', NULL, 'QR-GINECOBSTETRICIA', 'documental'),
(6, 'Imagenología', NULL, 'QR-IMAGENOLOGIA', 'documental'),
(7, 'Medicina Nuclear', NULL, 'QR-MEDICINA-NUCLEAR', 'documental');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `compatibilidad`
--

CREATE TABLE `compatibilidad` (
  `id_tipo_vehiculo` int(11) NOT NULL,
  `id_tipo_elemento` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `compatibilidad`
--

INSERT INTO `compatibilidad` (`id_tipo_vehiculo`, `id_tipo_elemento`) VALUES
(1, 1),
(1, 2),
(1, 3),
(1, 4),
(1, 5),
(1, 6),
(2, 1),
(2, 2),
(2, 3),
(2, 4),
(2, 5),
(2, 6),
(3, 5),
(3, 6);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `documento`
--

CREATE TABLE `documento` (
  `id_documento` int(11) NOT NULL,
  `archivo_url` varchar(255) NOT NULL,
  `fecha_carga` datetime NOT NULL DEFAULT current_timestamp(),
  `id_categoria` int(11) NOT NULL,
  `id_usuario_carga` int(11) NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `documento`
--

INSERT INTO `documento` (`id_documento`, `archivo_url`, `fecha_carga`, `id_categoria`, `id_usuario_carga`, `activo`) VALUES
(2, '/documentos/urologia/prostatectomia_radical.pdf', '2026-08-31 20:26:15', 1, 1, 1),
(4, 'documentos/1788374470_Informe_Urologia_Tratamiento.pdf', '2026-09-02 13:41:10', 2, 4, 0);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `encuesta`
--

CREATE TABLE `encuesta` (
  `id_encuesta` int(11) NOT NULL,
  `id_categoria` int(11) NOT NULL,
  `segmento` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `estado_traslado`
--

CREATE TABLE `estado_traslado` (
  `id_estado` int(11) NOT NULL,
  `nombre` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `estado_traslado`
--

INSERT INTO `estado_traslado` (`id_estado`, `nombre`) VALUES
(2, 'Asignado'),
(7, 'Cancelado'),
(3, 'En curso'),
(4, 'En destino'),
(5, 'En retorno'),
(6, 'Finalizado'),
(1, 'Solicitado');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `historial_estado`
--

CREATE TABLE `historial_estado` (
  `id_historial` int(11) NOT NULL,
  `id_traslado` int(11) NOT NULL,
  `id_estado` int(11) NOT NULL,
  `fecha_hora` datetime NOT NULL DEFAULT current_timestamp(),
  `id_usuario` int(11) NOT NULL,
  `observaciones` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `historial_estado`
--

INSERT INTO `historial_estado` (`id_historial`, `id_traslado`, `id_estado`, `fecha_hora`, `id_usuario`, `observaciones`) VALUES
(1, 1, 1, '2026-08-31 20:19:47', 1, 'Traslado solicitado'),
(2, 1, 2, '2026-08-31 20:21:27', 3, 'Vehículo y personal asignados');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `opcion_respuesta`
--

CREATE TABLE `opcion_respuesta` (
  `id_opcion` int(11) NOT NULL,
  `id_pregunta` int(11) NOT NULL,
  `texto_opcion` varchar(150) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `paciente`
--

CREATE TABLE `paciente` (
  `ci` varchar(20) NOT NULL,
  `nombre` varchar(80) NOT NULL,
  `apellido` varchar(80) NOT NULL,
  `fecha_nacimiento` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `paciente`
--

INSERT INTO `paciente` (`ci`, `nombre`, `apellido`, `fecha_nacimiento`) VALUES
('11111111', 'Hebelyn', 'Coria', '1998-05-12'),
('22222222', 'Simon', 'Coria', '2018-09-20');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pregunta`
--

CREATE TABLE `pregunta` (
  `id_pregunta` int(11) NOT NULL,
  `id_encuesta` int(11) NOT NULL,
  `tipo_pregunta` enum('abierta','opcion_unica','opcion_multiple','escala') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `proveedor_externo`
--

CREATE TABLE `proveedor_externo` (
  `id_proveedor` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `proveedor_externo`
--

INSERT INTO `proveedor_externo` (`id_proveedor`, `nombre`) VALUES
(1, 'SAME');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `respuesta_encuesta`
--

CREATE TABLE `respuesta_encuesta` (
  `id_resp_encuesta` int(11) NOT NULL,
  `id_encuesta` int(11) NOT NULL,
  `fecha_envio` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `respuesta_pregunta`
--

CREATE TABLE `respuesta_pregunta` (
  `id_resp_pregunta` int(11) NOT NULL,
  `id_resp_encuesta` int(11) NOT NULL,
  `id_pregunta` int(11) NOT NULL,
  `id_opcion` int(11) DEFAULT NULL,
  `texto_libre` varchar(500) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `rol`
--

CREATE TABLE `rol` (
  `id_rol` int(11) NOT NULL,
  `nombre_rol` varchar(50) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `rol`
--

INSERT INTO `rol` (`id_rol`, `nombre_rol`, `descripcion`) VALUES
(1, 'Administrador de DTI', NULL),
(2, 'Administrador de Enfermería', NULL),
(3, 'Administrador de Departamento de Transporte', NULL),
(4, 'Funcionario Administrativo de Registros Médicos', NULL),
(5, 'Chofer', NULL),
(6, 'Enfermero de Traslado', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ruta`
--

CREATE TABLE `ruta` (
  `id_ruta` int(11) NOT NULL,
  `id_origen` int(11) NOT NULL,
  `id_destino` int(11) NOT NULL,
  `distancia_km` decimal(8,2) DEFAULT NULL,
  `tiempo_min` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `ruta`
--

INSERT INTO `ruta` (`id_ruta`, `id_origen`, `id_destino`, `distancia_km`, `tiempo_min`) VALUES
(1, 1, 2, NULL, NULL),
(2, 1, 3, NULL, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `solicitud_traslado`
--

CREATE TABLE `solicitud_traslado` (
  `id_solicitud` int(11) NOT NULL,
  `ci_paciente` varchar(20) DEFAULT NULL,
  `id_tipo_elemento` int(11) NOT NULL,
  `id_origen` int(11) NOT NULL,
  `id_destino` int(11) NOT NULL,
  `id_canal` int(11) NOT NULL,
  `id_usuario_solicit` int(11) NOT NULL,
  `fecha_solicitud` datetime NOT NULL DEFAULT current_timestamp(),
  `estado_solicitud` varchar(50) NOT NULL,
  `prioridad` enum('normal','alta','maxima') NOT NULL DEFAULT 'normal',
  `fecha_hora_limite` datetime DEFAULT NULL,
  `observaciones` varchar(500) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `solicitud_traslado`
--

INSERT INTO `solicitud_traslado` (`id_solicitud`, `ci_paciente`, `id_tipo_elemento`, `id_origen`, `id_destino`, `id_canal`, `id_usuario_solicit`, `fecha_solicitud`, `estado_solicitud`, `prioridad`, `fecha_hora_limite`, `observaciones`) VALUES
(2, '11111111', 1, 1, 2, 1, 1, '2026-08-31 20:09:29', 'Pendiente', 'normal', NULL, NULL),
(3, '11111111', 1, 1, 3, 1, 1, '2026-08-31 23:40:33', 'Pendiente', 'normal', NULL, NULL),
(4, NULL, 5, 1, 2, 2, 4, '2026-08-31 23:40:33', 'Pendiente', 'normal', NULL, NULL),
(5, NULL, 6, 1, 3, 3, 4, '2026-08-31 23:40:33', 'Pendiente', 'normal', NULL, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tipo_elemento`
--

CREATE TABLE `tipo_elemento` (
  `id_tipo_elemento` int(11) NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `tipo_elemento`
--

INSERT INTO `tipo_elemento` (`id_tipo_elemento`, `nombre`, `descripcion`) VALUES
(1, 'Paciente', 'Traslado de paciente'),
(2, 'Órgano', 'Traslado de órgano'),
(3, 'Muestra biológica', 'Traslado de muestra biológica'),
(4, 'Cadetería de trámites', 'Traslado relacionado con trámites'),
(5, 'Insumo médico', 'Traslado de insumos médicos'),
(6, 'Equipamiento', 'Traslado de equipamiento');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tipo_vehiculo`
--

CREATE TABLE `tipo_vehiculo` (
  `id_tipo_vehiculo` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `tipo_vehiculo`
--

INSERT INTO `tipo_vehiculo` (`id_tipo_vehiculo`, `nombre`, `descripcion`) VALUES
(1, 'Ambulancia', NULL),
(2, 'Auto', NULL),
(3, 'Camión', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `traslado`
--

CREATE TABLE `traslado` (
  `id_traslado` int(11) NOT NULL,
  `id_solicitud` int(11) NOT NULL,
  `id_vehiculo` int(11) DEFAULT NULL,
  `id_chofer` int(11) DEFAULT NULL,
  `id_enfermero` int(11) DEFAULT NULL,
  `id_proveedor` int(11) DEFAULT NULL,
  `id_ruta` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `traslado`
--

INSERT INTO `traslado` (`id_traslado`, `id_solicitud`, `id_vehiculo`, `id_chofer`, `id_enfermero`, `id_proveedor`, `id_ruta`) VALUES
(1, 2, 1, 5, 6, NULL, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ubicacion`
--

CREATE TABLE `ubicacion` (
  `id_ubicacion` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `direccion` varchar(255) DEFAULT NULL,
  `tipo_ubicacion` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `ubicacion`
--

INSERT INTO `ubicacion` (`id_ubicacion`, `nombre`, `direccion`, `tipo_ubicacion`) VALUES
(1, 'Hospital de Clínicas', NULL, 'Hospital'),
(2, 'Hospital Maciel', NULL, 'Hospital'),
(3, 'CHPR', NULL, 'Hospital');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuario`
--

CREATE TABLE `usuario` (
  `id_usuario` int(11) NOT NULL,
  `nombre_usuario` varchar(50) NOT NULL,
  `contrasenha_hash` varchar(255) NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `usuario`
--

INSERT INTO `usuario` (`id_usuario`, `nombre_usuario`, `contrasenha_hash`, `activo`) VALUES
(1, 'admin_dti', 'contra1', 1),
(2, 'itanu_miniño', 'contra2', 1),
(3, 'admin_transporte', 'contra3', 1),
(4, 'registros_medicos', 'contra4', 1),
(5, 'pedro_minino', 'contra5', 1),
(6, 'lucia_pereira', 'contra6', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuario_rol`
--

CREATE TABLE `usuario_rol` (
  `id_usuario` int(11) NOT NULL,
  `id_rol` int(11) NOT NULL,
  `fecha_asignacion` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `usuario_rol`
--

INSERT INTO `usuario_rol` (`id_usuario`, `id_rol`, `fecha_asignacion`) VALUES
(1, 1, '2026-08-31 19:08:43'),
(2, 2, '2026-08-31 19:08:43'),
(3, 3, '2026-08-31 19:08:43'),
(4, 4, '2026-08-31 19:08:43'),
(5, 5, '2026-08-31 19:08:43'),
(6, 6, '2026-08-31 19:08:43');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `vehiculo`
--

CREATE TABLE `vehiculo` (
  `id_vehiculo` int(11) NOT NULL,
  `matricula` varchar(20) NOT NULL,
  `marca` varchar(60) NOT NULL,
  `modelo` varchar(60) NOT NULL,
  `año` int(11) NOT NULL,
  `id_tipo_vehiculo` int(11) NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `vehiculo`
--

INSERT INTO `vehiculo` (`id_vehiculo`, `matricula`, `marca`, `modelo`, `año`, `id_tipo_vehiculo`, `activo`) VALUES
(1, 'AMB001', 'Mercedes-Benz', 'Sprinter', 2022, 1, 1),
(2, 'AUT001', 'Chevrolet', 'Onix', 2021, 2, 1),
(3, 'CAM001', 'JAC', 'N-Series', 2020, 3, 1);

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `canal_solicitud`
--
ALTER TABLE `canal_solicitud`
  ADD PRIMARY KEY (`id_canal`),
  ADD UNIQUE KEY `nombre` (`nombre`);

--
-- Indices de la tabla `categoria`
--
ALTER TABLE `categoria`
  ADD PRIMARY KEY (`id_categoria`),
  ADD UNIQUE KEY `nombre` (`nombre`),
  ADD UNIQUE KEY `codigo_qr` (`codigo_qr`);

--
-- Indices de la tabla `compatibilidad`
--
ALTER TABLE `compatibilidad`
  ADD PRIMARY KEY (`id_tipo_vehiculo`,`id_tipo_elemento`),
  ADD KEY `fk_compatibilidad_elemento` (`id_tipo_elemento`);

--
-- Indices de la tabla `documento`
--
ALTER TABLE `documento`
  ADD PRIMARY KEY (`id_documento`),
  ADD KEY `fk_documento_categoria` (`id_categoria`),
  ADD KEY `fk_documento_usuario` (`id_usuario_carga`);

--
-- Indices de la tabla `encuesta`
--
ALTER TABLE `encuesta`
  ADD PRIMARY KEY (`id_encuesta`),
  ADD KEY `fk_encuesta_categoria` (`id_categoria`);

--
-- Indices de la tabla `estado_traslado`
--
ALTER TABLE `estado_traslado`
  ADD PRIMARY KEY (`id_estado`),
  ADD UNIQUE KEY `nombre` (`nombre`);

--
-- Indices de la tabla `historial_estado`
--
ALTER TABLE `historial_estado`
  ADD PRIMARY KEY (`id_historial`),
  ADD KEY `fk_historial_traslado` (`id_traslado`),
  ADD KEY `fk_historial_estado` (`id_estado`),
  ADD KEY `fk_historial_usuario` (`id_usuario`);

--
-- Indices de la tabla `opcion_respuesta`
--
ALTER TABLE `opcion_respuesta`
  ADD PRIMARY KEY (`id_opcion`),
  ADD KEY `fk_opcion_pregunta` (`id_pregunta`);

--
-- Indices de la tabla `paciente`
--
ALTER TABLE `paciente`
  ADD PRIMARY KEY (`ci`);

--
-- Indices de la tabla `pregunta`
--
ALTER TABLE `pregunta`
  ADD PRIMARY KEY (`id_pregunta`),
  ADD KEY `fk_pregunta_encuesta` (`id_encuesta`);

--
-- Indices de la tabla `proveedor_externo`
--
ALTER TABLE `proveedor_externo`
  ADD PRIMARY KEY (`id_proveedor`);

--
-- Indices de la tabla `respuesta_encuesta`
--
ALTER TABLE `respuesta_encuesta`
  ADD PRIMARY KEY (`id_resp_encuesta`),
  ADD KEY `fk_respuesta_encuesta` (`id_encuesta`);

--
-- Indices de la tabla `respuesta_pregunta`
--
ALTER TABLE `respuesta_pregunta`
  ADD PRIMARY KEY (`id_resp_pregunta`),
  ADD KEY `fk_resp_pregunta_encuesta` (`id_resp_encuesta`),
  ADD KEY `fk_resp_pregunta_pregunta` (`id_pregunta`),
  ADD KEY `fk_resp_pregunta_opcion` (`id_opcion`);

--
-- Indices de la tabla `rol`
--
ALTER TABLE `rol`
  ADD PRIMARY KEY (`id_rol`),
  ADD UNIQUE KEY `nombre_rol` (`nombre_rol`);

--
-- Indices de la tabla `ruta`
--
ALTER TABLE `ruta`
  ADD PRIMARY KEY (`id_ruta`),
  ADD KEY `fk_ruta_origen` (`id_origen`),
  ADD KEY `fk_ruta_destino` (`id_destino`);

--
-- Indices de la tabla `solicitud_traslado`
--
ALTER TABLE `solicitud_traslado`
  ADD PRIMARY KEY (`id_solicitud`),
  ADD KEY `fk_solicitud_paciente` (`ci_paciente`),
  ADD KEY `fk_solicitud_tipo_elemento` (`id_tipo_elemento`),
  ADD KEY `fk_solicitud_origen` (`id_origen`),
  ADD KEY `fk_solicitud_destino` (`id_destino`),
  ADD KEY `fk_solicitud_canal` (`id_canal`),
  ADD KEY `fk_solicitud_usuario` (`id_usuario_solicit`);

--
-- Indices de la tabla `tipo_elemento`
--
ALTER TABLE `tipo_elemento`
  ADD PRIMARY KEY (`id_tipo_elemento`),
  ADD UNIQUE KEY `nombre` (`nombre`);

--
-- Indices de la tabla `tipo_vehiculo`
--
ALTER TABLE `tipo_vehiculo`
  ADD PRIMARY KEY (`id_tipo_vehiculo`),
  ADD UNIQUE KEY `nombre` (`nombre`);

--
-- Indices de la tabla `traslado`
--
ALTER TABLE `traslado`
  ADD PRIMARY KEY (`id_traslado`),
  ADD UNIQUE KEY `id_solicitud` (`id_solicitud`),
  ADD KEY `fk_traslado_vehiculo` (`id_vehiculo`),
  ADD KEY `fk_traslado_chofer` (`id_chofer`),
  ADD KEY `fk_traslado_enfermero` (`id_enfermero`),
  ADD KEY `fk_traslado_proveedor` (`id_proveedor`),
  ADD KEY `fk_traslado_ruta` (`id_ruta`);

--
-- Indices de la tabla `ubicacion`
--
ALTER TABLE `ubicacion`
  ADD PRIMARY KEY (`id_ubicacion`);

--
-- Indices de la tabla `usuario`
--
ALTER TABLE `usuario`
  ADD PRIMARY KEY (`id_usuario`),
  ADD UNIQUE KEY `nombre_usuario` (`nombre_usuario`);

--
-- Indices de la tabla `usuario_rol`
--
ALTER TABLE `usuario_rol`
  ADD PRIMARY KEY (`id_usuario`,`id_rol`),
  ADD KEY `fk_usuario_rol_rol` (`id_rol`);

--
-- Indices de la tabla `vehiculo`
--
ALTER TABLE `vehiculo`
  ADD PRIMARY KEY (`id_vehiculo`),
  ADD UNIQUE KEY `matricula` (`matricula`),
  ADD KEY `fk_vehiculo_tipo` (`id_tipo_vehiculo`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `canal_solicitud`
--
ALTER TABLE `canal_solicitud`
  MODIFY `id_canal` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `categoria`
--
ALTER TABLE `categoria`
  MODIFY `id_categoria` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `documento`
--
ALTER TABLE `documento`
  MODIFY `id_documento` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `encuesta`
--
ALTER TABLE `encuesta`
  MODIFY `id_encuesta` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `estado_traslado`
--
ALTER TABLE `estado_traslado`
  MODIFY `id_estado` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `historial_estado`
--
ALTER TABLE `historial_estado`
  MODIFY `id_historial` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `opcion_respuesta`
--
ALTER TABLE `opcion_respuesta`
  MODIFY `id_opcion` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `pregunta`
--
ALTER TABLE `pregunta`
  MODIFY `id_pregunta` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `proveedor_externo`
--
ALTER TABLE `proveedor_externo`
  MODIFY `id_proveedor` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `respuesta_encuesta`
--
ALTER TABLE `respuesta_encuesta`
  MODIFY `id_resp_encuesta` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `respuesta_pregunta`
--
ALTER TABLE `respuesta_pregunta`
  MODIFY `id_resp_pregunta` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `rol`
--
ALTER TABLE `rol`
  MODIFY `id_rol` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `ruta`
--
ALTER TABLE `ruta`
  MODIFY `id_ruta` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `solicitud_traslado`
--
ALTER TABLE `solicitud_traslado`
  MODIFY `id_solicitud` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `tipo_elemento`
--
ALTER TABLE `tipo_elemento`
  MODIFY `id_tipo_elemento` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `tipo_vehiculo`
--
ALTER TABLE `tipo_vehiculo`
  MODIFY `id_tipo_vehiculo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `traslado`
--
ALTER TABLE `traslado`
  MODIFY `id_traslado` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `ubicacion`
--
ALTER TABLE `ubicacion`
  MODIFY `id_ubicacion` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `usuario`
--
ALTER TABLE `usuario`
  MODIFY `id_usuario` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de la tabla `vehiculo`
--
ALTER TABLE `vehiculo`
  MODIFY `id_vehiculo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `compatibilidad`
--
ALTER TABLE `compatibilidad`
  ADD CONSTRAINT `fk_compatibilidad_elemento` FOREIGN KEY (`id_tipo_elemento`) REFERENCES `tipo_elemento` (`id_tipo_elemento`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_compatibilidad_vehiculo` FOREIGN KEY (`id_tipo_vehiculo`) REFERENCES `tipo_vehiculo` (`id_tipo_vehiculo`) ON DELETE CASCADE;

--
-- Filtros para la tabla `documento`
--
ALTER TABLE `documento`
  ADD CONSTRAINT `fk_documento_categoria` FOREIGN KEY (`id_categoria`) REFERENCES `categoria` (`id_categoria`),
  ADD CONSTRAINT `fk_documento_usuario` FOREIGN KEY (`id_usuario_carga`) REFERENCES `usuario` (`id_usuario`);

--
-- Filtros para la tabla `encuesta`
--
ALTER TABLE `encuesta`
  ADD CONSTRAINT `fk_encuesta_categoria` FOREIGN KEY (`id_categoria`) REFERENCES `categoria` (`id_categoria`);

--
-- Filtros para la tabla `historial_estado`
--
ALTER TABLE `historial_estado`
  ADD CONSTRAINT `fk_historial_estado` FOREIGN KEY (`id_estado`) REFERENCES `estado_traslado` (`id_estado`),
  ADD CONSTRAINT `fk_historial_traslado` FOREIGN KEY (`id_traslado`) REFERENCES `traslado` (`id_traslado`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_historial_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`);

--
-- Filtros para la tabla `opcion_respuesta`
--
ALTER TABLE `opcion_respuesta`
  ADD CONSTRAINT `fk_opcion_pregunta` FOREIGN KEY (`id_pregunta`) REFERENCES `pregunta` (`id_pregunta`) ON DELETE CASCADE;

--
-- Filtros para la tabla `pregunta`
--
ALTER TABLE `pregunta`
  ADD CONSTRAINT `fk_pregunta_encuesta` FOREIGN KEY (`id_encuesta`) REFERENCES `encuesta` (`id_encuesta`) ON DELETE CASCADE;

--
-- Filtros para la tabla `respuesta_encuesta`
--
ALTER TABLE `respuesta_encuesta`
  ADD CONSTRAINT `fk_respuesta_encuesta` FOREIGN KEY (`id_encuesta`) REFERENCES `encuesta` (`id_encuesta`);

--
-- Filtros para la tabla `respuesta_pregunta`
--
ALTER TABLE `respuesta_pregunta`
  ADD CONSTRAINT `fk_resp_pregunta_encuesta` FOREIGN KEY (`id_resp_encuesta`) REFERENCES `respuesta_encuesta` (`id_resp_encuesta`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_resp_pregunta_opcion` FOREIGN KEY (`id_opcion`) REFERENCES `opcion_respuesta` (`id_opcion`),
  ADD CONSTRAINT `fk_resp_pregunta_pregunta` FOREIGN KEY (`id_pregunta`) REFERENCES `pregunta` (`id_pregunta`);

--
-- Filtros para la tabla `ruta`
--
ALTER TABLE `ruta`
  ADD CONSTRAINT `fk_ruta_destino` FOREIGN KEY (`id_destino`) REFERENCES `ubicacion` (`id_ubicacion`),
  ADD CONSTRAINT `fk_ruta_origen` FOREIGN KEY (`id_origen`) REFERENCES `ubicacion` (`id_ubicacion`);

--
-- Filtros para la tabla `solicitud_traslado`
--
ALTER TABLE `solicitud_traslado`
  ADD CONSTRAINT `fk_solicitud_canal` FOREIGN KEY (`id_canal`) REFERENCES `canal_solicitud` (`id_canal`),
  ADD CONSTRAINT `fk_solicitud_destino` FOREIGN KEY (`id_destino`) REFERENCES `ubicacion` (`id_ubicacion`),
  ADD CONSTRAINT `fk_solicitud_origen` FOREIGN KEY (`id_origen`) REFERENCES `ubicacion` (`id_ubicacion`),
  ADD CONSTRAINT `fk_solicitud_paciente` FOREIGN KEY (`ci_paciente`) REFERENCES `paciente` (`ci`),
  ADD CONSTRAINT `fk_solicitud_tipo_elemento` FOREIGN KEY (`id_tipo_elemento`) REFERENCES `tipo_elemento` (`id_tipo_elemento`),
  ADD CONSTRAINT `fk_solicitud_usuario` FOREIGN KEY (`id_usuario_solicit`) REFERENCES `usuario` (`id_usuario`);

--
-- Filtros para la tabla `traslado`
--
ALTER TABLE `traslado`
  ADD CONSTRAINT `fk_traslado_chofer` FOREIGN KEY (`id_chofer`) REFERENCES `usuario` (`id_usuario`),
  ADD CONSTRAINT `fk_traslado_enfermero` FOREIGN KEY (`id_enfermero`) REFERENCES `usuario` (`id_usuario`),
  ADD CONSTRAINT `fk_traslado_proveedor` FOREIGN KEY (`id_proveedor`) REFERENCES `proveedor_externo` (`id_proveedor`),
  ADD CONSTRAINT `fk_traslado_ruta` FOREIGN KEY (`id_ruta`) REFERENCES `ruta` (`id_ruta`),
  ADD CONSTRAINT `fk_traslado_solicitud` FOREIGN KEY (`id_solicitud`) REFERENCES `solicitud_traslado` (`id_solicitud`),
  ADD CONSTRAINT `fk_traslado_vehiculo` FOREIGN KEY (`id_vehiculo`) REFERENCES `vehiculo` (`id_vehiculo`);

--
-- Filtros para la tabla `usuario_rol`
--
ALTER TABLE `usuario_rol`
  ADD CONSTRAINT `fk_usuario_rol_rol` FOREIGN KEY (`id_rol`) REFERENCES `rol` (`id_rol`),
  ADD CONSTRAINT `fk_usuario_rol_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`) ON DELETE CASCADE;

--
-- Filtros para la tabla `vehiculo`
--
ALTER TABLE `vehiculo`
  ADD CONSTRAINT `fk_vehiculo_tipo` FOREIGN KEY (`id_tipo_vehiculo`) REFERENCES `tipo_vehiculo` (`id_tipo_vehiculo`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
