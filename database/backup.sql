-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 28-12-2025 a las 21:15:01
-- Versión del servidor: 10.4.28-MariaDB
-- Versión de PHP: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `sistema_entrenamiento`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `biblioteca_ejercicios`
--

CREATE TABLE `biblioteca_ejercicios` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL COMMENT 'Usuario que creó el ejercicio',
  `categoria_id` int(11) NOT NULL,
  `nombre_ejercicio` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `imagen_url` varchar(255) DEFAULT NULL,
  `video_url` varchar(255) DEFAULT NULL,
  `es_publico` tinyint(1) DEFAULT 1 COMMENT 'Visible para todos',
  `es_privado` tinyint(1) DEFAULT 0 COMMENT 'Si es 1, URLs visibles solo para el creador',
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `biblioteca_ejercicios`
--

INSERT INTO `biblioteca_ejercicios` (`id`, `usuario_id`, `categoria_id`, `nombre_ejercicio`, `descripcion`, `imagen_url`, `video_url`, `es_publico`, `es_privado`, `fecha_creacion`) VALUES
(1, 1, 1, 'Press de Banca', 'Ejercicio principal para pecho', NULL, NULL, 1, 0, '2025-12-28 03:53:58'),
(2, 1, 1, 'Aperturas con Mancuernas', 'Aislamiento de pecho', NULL, NULL, 1, 0, '2025-12-28 03:53:58'),
(3, 1, 6, 'Sentadillas', 'Ejercicio compuesto para piernas', NULL, NULL, 1, 0, '2025-12-28 03:53:58'),
(4, 1, 6, 'Prensa de Piernas', 'Ejercicio de fuerza para piernas', NULL, NULL, 1, 0, '2025-12-28 03:53:58'),
(5, 1, 7, 'Extensiones de Cuádriceps', 'Aislamiento de cuádriceps', NULL, NULL, 1, 0, '2025-12-28 03:53:58'),
(6, 1, 8, 'Curl Femoral', 'Aislamiento de femorales', NULL, NULL, 1, 0, '2025-12-28 03:53:58'),
(7, 1, 2, 'Dominadas', 'Ejercicio compuesto para espalda', NULL, NULL, 1, 0, '2025-12-28 03:53:58'),
(8, 1, 2, 'Remo con Barra', 'Ejercicio de espalda', NULL, NULL, 1, 0, '2025-12-28 03:53:58'),
(9, 1, 3, 'Press militar', 'mover con tranquilidad y todo controlado', 'https://s3assets.skimble.com/assets/2289478/image_full.jpg', 'https://www.youtube.com/watch?v=SCVCLChPQFY', 0, 0, '2025-12-28 04:56:49'),
(10, 1, 11, 'crunch', 'controlado', 'https://s3assets.skimble.com/assets/2289478/image_full.jpg', 'https://www.youtube.com/watch?v=SCVCLChPQFY', 1, 0, '2025-12-28 04:57:57');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `calificaciones_rutinas`
--

CREATE TABLE `calificaciones_rutinas` (
  `id` int(11) NOT NULL,
  `rutina_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `calificacion` int(11) NOT NULL CHECK (`calificacion` between 1 and 5),
  `comentario` text DEFAULT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `categorias_ejercicios`
--

CREATE TABLE `categorias_ejercicios` (
  `id` int(11) NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `icono` varchar(10) DEFAULT NULL COMMENT 'Emoji del grupo muscular'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `categorias_ejercicios`
--

INSERT INTO `categorias_ejercicios` (`id`, `nombre`, `icono`) VALUES
(1, 'Pecho', '💪'),
(2, 'Espalda', '🦾'),
(3, 'Hombros', '💪'),
(4, 'Bíceps', '💪'),
(5, 'Tríceps', '💪'),
(6, 'Piernas', '🦵'),
(7, 'Cuádriceps', '🦵'),
(8, 'Femorales', '🦵'),
(9, 'Glúteos', '🍑'),
(10, 'Pantorrillas', '🦵'),
(11, 'Abdominales', '🔥'),
(12, 'Core', '🔥');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `contenido_aprendizaje`
--

CREATE TABLE `contenido_aprendizaje` (
  `id` int(11) NOT NULL,
  `modulo_id` int(11) NOT NULL,
  `creador_id` int(11) NOT NULL,
  `titulo` varchar(100) NOT NULL,
  `contenido` text DEFAULT NULL,
  `video_url` varchar(255) DEFAULT NULL,
  `imagen_url` varchar(255) DEFAULT NULL,
  `orden` int(11) DEFAULT 0,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `dias_rutina`
--

CREATE TABLE `dias_rutina` (
  `id` int(11) NOT NULL,
  `rutina_id` int(11) NOT NULL,
  `dia_semana` enum('Lunes','Martes','Miércoles','Jueves','Viernes','Sábado','Domingo') NOT NULL,
  `num_ejercicios` int(11) DEFAULT 3 COMMENT 'Número de ejercicios para este día',
  `grupos_musculares` varchar(255) DEFAULT NULL COMMENT 'Grupos musculares del día (ej: Pecho, Bíceps, Abs)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `dias_rutina`
--

INSERT INTO `dias_rutina` (`id`, `rutina_id`, `dia_semana`, `num_ejercicios`, `grupos_musculares`) VALUES
(1, 1, 'Lunes', 4, NULL),
(2, 1, 'Martes', 3, NULL),
(3, 1, 'Jueves', 3, NULL),
(4, 1, 'Viernes', 3, NULL),
(5, 2, 'Lunes', 3, NULL),
(6, 2, 'Martes', 3, NULL),
(7, 2, 'Miércoles', 3, NULL),
(8, 2, 'Jueves', 3, NULL),
(9, 3, 'Lunes', 3, NULL),
(10, 3, 'Martes', 3, NULL),
(11, 3, 'Jueves', 3, NULL),
(12, 3, 'Viernes', 3, NULL),
(13, 4, 'Lunes', 3, 'pecho espalda biceps'),
(14, 4, 'Martes', 3, 'cuadriceps-femorales-piernas'),
(15, 4, 'Miércoles', 3, 'abodminales y piernas'),
(16, 4, 'Jueves', 3, 'pecho, piernas'),
(17, 5, 'Lunes', 3, 'pecho espalda biceps'),
(18, 5, 'Martes', 3, 'cuadriceps-femorales-piernas'),
(19, 5, 'Viernes', 3, 'abdomnales y piernas'),
(20, 6, 'Lunes', 3, 'pecho espalda biceps'),
(21, 6, 'Martes', 3, 'cuadriceps-femorales-piernas'),
(22, 6, 'Miércoles', 3, 'abodminales y piernas'),
(23, 6, 'Jueves', 3, 'pecho, piernas'),
(24, 7, 'Lunes', 3, 'pecho espalda biceps'),
(25, 7, 'Martes', 3, 'cuadriceps-femorales-piernas'),
(26, 7, 'Miércoles', 3, 'abodminales y piernas'),
(27, 7, 'Jueves', 3, 'pecho, piernas'),
(28, 8, 'Lunes', 3, 'pecho biceps'),
(29, 8, 'Martes', 3, 'dorsale'),
(30, 8, 'Miércoles', 3, 'piernas'),
(31, 8, 'Jueves', 3, 'abdominales'),
(32, 9, 'Lunes', 3, 'pecho biceps'),
(33, 9, 'Martes', 3, 'dorsale'),
(34, 9, 'Miércoles', 3, 'piernas'),
(35, 9, 'Domingo', 3, 'abs');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ejercicios`
--

CREATE TABLE `ejercicios` (
  `id` int(11) NOT NULL,
  `dia_rutina_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `orden` int(11) NOT NULL COMMENT 'Orden del ejercicio en el día (1, 2, 3...)',
  `nombre_ejercicio` varchar(100) NOT NULL,
  `imagen_url` varchar(255) DEFAULT NULL,
  `video_url` varchar(255) DEFAULT NULL,
  `objetivo_serie` varchar(100) DEFAULT NULL,
  `num_series` int(11) DEFAULT 3,
  `num_sesiones` int(11) DEFAULT 4,
  `descanso_minutos` int(11) DEFAULT 2,
  `descanso_segundos` int(11) DEFAULT 0,
  `rir_rpe` varchar(20) DEFAULT NULL COMMENT 'RIR o RPE por defecto',
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `ejercicios`
--

INSERT INTO `ejercicios` (`id`, `dia_rutina_id`, `usuario_id`, `orden`, `nombre_ejercicio`, `imagen_url`, `video_url`, `objetivo_serie`, `num_series`, `num_sesiones`, `descanso_minutos`, `descanso_segundos`, `rir_rpe`, `fecha_creacion`) VALUES
(1, 9, 2, 1, 'Beanch press', 'https://s3assets.skimble.com/assets/2289478/image_full.jpg', 'https://www.youtube.com/watch?v=SCVCLChPQFY', 'Top Set back of', 4, 4, 2, 0, '2-3', '2025-12-28 03:00:10'),
(2, 13, 2, 1, 'Beanch press', 'https://s3assets.skimble.com/assets/2289478/image_full.jpg', 'https://www.youtube.com/watch?v=SCVCLChPQFY', 'Top Set', 3, 4, 2, 30, '2-3', '2025-12-28 03:13:03'),
(3, 13, 2, 2, 'sentadilla', 'https://s3assets.skimble.com/assets/2289478/image_full.jpg', 'https://www.youtube.com/watch?v=SCVCLChPQFY', 'Top Set', 3, 4, 2, 0, '2-3', '2025-12-28 03:13:54'),
(4, 17, 2, 1, 'Beanch press', 'https://s3assets.skimble.com/assets/2289478/image_full.jpg', 'https://www.youtube.com/watch?v=SCVCLChPQFY', 'Top Set', 4, 4, 2, 0, '2-3', '2025-12-28 03:21:07'),
(5, 19, 2, 1, 'sentadilla', 'https://s3assets.skimble.com/assets/2289478/image_full.jpg', 'https://www.youtube.com/watch?v=SCVCLChPQFY', 'Top Set', 4, 4, 2, 0, '2-3', '2025-12-28 03:33:12'),
(6, 19, 2, 2, 'crunch', 'https://s3assets.skimble.com/assets/2289478/image_full.jpg', 'https://www.youtube.com/watch?v=SCVCLChPQFY', 'Top Set', 3, 4, 2, 0, '2-3', '2025-12-28 03:34:49'),
(7, 20, 2, 1, 'Beanch press', 'https://s3assets.skimble.com/assets/2289478/image_full.jpg', 'https://www.youtube.com/watch?v=SCVCLChPQFY', 'Top Set', 3, 4, 2, 0, '2-3', '2025-12-28 04:03:17'),
(8, 21, 2, 1, 'sentadilla', 'https://s3assets.skimble.com/assets/2289478/image_full.jpg', 'https://www.youtube.com/watch?v=SCVCLChPQFY', 'Top Set', 3, 4, 2, 0, '2-3', '2025-12-28 04:08:07'),
(9, 24, 1, 1, 'Beanch press', 'https://s3assets.skimble.com/assets/2289478/image_full.jpg', 'https://www.youtube.com/watch?v=SCVCLChPQFY', 'Top Set', 3, 4, 2, 0, '2-3', '2025-12-28 05:12:22'),
(10, 25, 1, 1, 'Beanch press', 'https://s3assets.skimble.com/assets/2289478/image_full.jpg', 'https://www.youtube.com/watch?v=SCVCLChPQFY', 'Top Set', 3, 4, 2, 0, '2-3', '2025-12-28 05:12:44');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ejercicios_basicos`
--

CREATE TABLE `ejercicios_basicos` (
  `id` int(11) NOT NULL,
  `rutina_id` int(11) NOT NULL,
  `dia_semana` enum('Lunes','Martes','Miércoles','Jueves','Viernes','Sábado','Domingo') NOT NULL,
  `orden` int(11) NOT NULL COMMENT 'Orden del ejercicio dentro del día',
  `nombre_ejercicio` varchar(100) NOT NULL,
  `imagen_url` varchar(255) DEFAULT NULL,
  `video_url` varchar(255) DEFAULT NULL,
  `notas` text DEFAULT NULL,
  `series_reps` varchar(50) DEFAULT NULL COMMENT 'Ej: 4 series, 15-20 reps',
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `entrenador_clientes`
--

CREATE TABLE `entrenador_clientes` (
  `id` int(11) NOT NULL,
  `entrenador_id` int(11) NOT NULL,
  `cliente_id` int(11) NOT NULL,
  `fecha_inicio` date NOT NULL,
  `fecha_fin` date NOT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `alerta_enviada` tinyint(1) DEFAULT 0 COMMENT 'Si ya se envió alerta de vencimiento',
  `notas` text DEFAULT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `evaluaciones_serie`
--

CREATE TABLE `evaluaciones_serie` (
  `id` int(11) NOT NULL,
  `ejercicio_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `num_sesion` int(11) NOT NULL,
  `num_serie` int(11) NOT NULL,
  `evaluacion` enum('✓','✗','~') DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `historial_precios`
--

CREATE TABLE `historial_precios` (
  `id` int(11) NOT NULL,
  `plan_precio_id` int(11) NOT NULL,
  `precio_anterior` decimal(10,2) NOT NULL,
  `precio_nuevo` decimal(10,2) NOT NULL,
  `usuario_modifico` int(11) NOT NULL COMMENT 'Admin que hizo el cambio',
  `fecha_cambio` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `metricas_clientes`
--

CREATE TABLE `metricas_clientes` (
  `id` int(11) NOT NULL,
  `cliente_id` int(11) NOT NULL,
  `entrenador_id` int(11) NOT NULL,
  `fecha_registro` date NOT NULL,
  `peso` decimal(5,2) DEFAULT NULL COMMENT 'Peso en kg',
  `porcentaje_grasa` decimal(4,2) DEFAULT NULL COMMENT 'Porcentaje de grasa corporal',
  `pecho` decimal(5,2) DEFAULT NULL,
  `cintura` decimal(5,2) DEFAULT NULL,
  `cadera` decimal(5,2) DEFAULT NULL,
  `brazo_derecho` decimal(5,2) DEFAULT NULL,
  `brazo_izquierdo` decimal(5,2) DEFAULT NULL,
  `pierna_derecha` decimal(5,2) DEFAULT NULL,
  `pierna_izquierda` decimal(5,2) DEFAULT NULL,
  `notas` text DEFAULT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `modulos_aprendizaje`
--

CREATE TABLE `modulos_aprendizaje` (
  `id` int(11) NOT NULL,
  `creador_id` int(11) NOT NULL,
  `titulo` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `icono` varchar(10) DEFAULT '?',
  `orden` int(11) DEFAULT 0,
  `es_destacado` tinyint(1) DEFAULT 0 COMMENT 'Solo admin puede marcar como destacado',
  `activo` tinyint(1) DEFAULT 1,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `modulos_aprendizaje`
--

INSERT INTO `modulos_aprendizaje` (`id`, `creador_id`, `titulo`, `descripcion`, `icono`, `orden`, `es_destacado`, `activo`, `fecha_creacion`) VALUES
(1, 1, 'Ejercicios Básicos', 'Aprende los ejercicios fundamentales del entrenamiento', '💪', 1, 1, 1, '2025-12-28 20:01:26'),
(2, 1, 'División Torso-Piernas', 'Guía completa sobre rutinas Torso-Piernas', '🏋️', 2, 1, 1, '2025-12-28 20:01:26'),
(3, 1, 'Cronometrar Descanso', 'Aprende a gestionar tus tiempos de descanso', '⏱️', 3, 0, 1, '2025-12-28 20:01:26'),
(4, 1, 'Ejercicios Multi-articulares', 'Ejercicios compuestos para máxima eficiencia', '🔥', 4, 1, 1, '2025-12-28 20:01:26'),
(5, 1, 'Ejercicios Mono-articulares', 'Ejercicios de aislamiento para grupos específicos', '🎯', 5, 0, 1, '2025-12-28 20:01:26'),
(6, 1, 'Bracing Abdominal', 'Técnica para proteger tu espalda y mejorar fuerza', '🛡️', 6, 1, 1, '2025-12-28 20:01:26');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `notas_sesion`
--

CREATE TABLE `notas_sesion` (
  `id` int(11) NOT NULL,
  `ejercicio_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `num_sesion` int(11) NOT NULL,
  `nota` text NOT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `notificaciones`
--

CREATE TABLE `notificaciones` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `tipo` varchar(50) NOT NULL COMMENT 'vencimiento_plan, nueva_rutina, etc',
  `titulo` varchar(200) NOT NULL,
  `mensaje` text NOT NULL,
  `leido` tinyint(1) DEFAULT 0,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `planes_precios`
--

CREATE TABLE `planes_precios` (
  `id` int(11) NOT NULL,
  `plan_id` int(11) NOT NULL,
  `duracion_meses` int(11) NOT NULL COMMENT '1, 6, 12 meses',
  `precio_mensual` decimal(10,2) NOT NULL COMMENT 'Precio por mes',
  `precio_total` decimal(10,2) NOT NULL COMMENT 'Precio total del periodo',
  `descuento_porcentaje` int(11) DEFAULT 0,
  `activo` tinyint(1) DEFAULT 1,
  `fecha_actualizacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `planes_precios`
--

INSERT INTO `planes_precios` (`id`, `plan_id`, `duracion_meses`, `precio_mensual`, `precio_total`, `descuento_porcentaje`, `activo`, `fecha_actualizacion`) VALUES
(1, 1, 1, 29.00, 29.00, 0, 1, '2025-12-28 04:48:02'),
(2, 1, 6, 24.00, 144.00, 17, 1, '2025-12-28 04:48:02'),
(3, 1, 12, 19.00, 228.00, 34, 1, '2025-12-28 04:48:02'),
(4, 2, 1, 69.00, 69.00, 0, 1, '2025-12-28 04:48:02'),
(5, 2, 6, 59.00, 354.00, 14, 1, '2025-12-28 04:48:02'),
(6, 2, 12, 49.00, 588.00, 29, 1, '2025-12-28 04:48:02'),
(7, 3, 1, 129.00, 129.00, 0, 1, '2025-12-28 04:48:02'),
(8, 3, 6, 109.00, 654.00, 15, 1, '2025-12-28 04:48:02'),
(9, 3, 12, 89.00, 1068.00, 31, 1, '2025-12-28 04:48:02');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `planes_pro`
--

CREATE TABLE `planes_pro` (
  `id` int(11) NOT NULL,
  `nombre` varchar(50) NOT NULL COMMENT 'starter, professional, unlimited',
  `nombre_display` varchar(100) NOT NULL COMMENT 'Nombre para mostrar',
  `max_clientes` int(11) NOT NULL COMMENT 'Número máximo de clientes, 0 = ilimitado',
  `activo` tinyint(1) DEFAULT 1,
  `orden` int(11) DEFAULT 0 COMMENT 'Orden de visualización',
  `descripcion` text DEFAULT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `planes_pro`
--

INSERT INTO `planes_pro` (`id`, `nombre`, `nombre_display`, `max_clientes`, `activo`, `orden`, `descripcion`, `fecha_creacion`) VALUES
(1, 'starter', '🥉 Plan Starter', 10, 1, 1, 'Ideal para entrenadores comenzando. Hasta 10 clientes.', '2025-12-28 04:48:02'),
(2, 'professional', '🥈 Plan Professional', 30, 1, 2, 'Para entrenadores establecidos. Hasta 30 clientes.', '2025-12-28 04:48:02'),
(3, 'unlimited', '🥇 Plan Unlimited', 0, 1, 3, 'Sin límites. Clientes ilimitados.', '2025-12-28 04:48:02');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `rutinas`
--

CREATE TABLE `rutinas` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `nombre_rutina` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `descripcion_split` text DEFAULT NULL,
  `video_explicativo` varchar(255) DEFAULT NULL,
  `calificacion_promedio` decimal(2,1) DEFAULT 0.0,
  `total_votos` int(11) DEFAULT 0,
  `num_dias_semana` int(11) NOT NULL COMMENT 'Días de entrenamiento por semana',
  `tipo_rutina` enum('metodologica','basica') DEFAULT 'metodologica',
  `genero` enum('masculino','femenino','unisex') DEFAULT 'unisex',
  `nivel_experiencia` enum('principiante','intermedio','avanzado') DEFAULT 'principiante',
  `es_publico` tinyint(1) DEFAULT 0,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `rutinas`
--

INSERT INTO `rutinas` (`id`, `usuario_id`, `nombre_rutina`, `descripcion`, `descripcion_split`, `video_explicativo`, `calificacion_promedio`, `total_votos`, `num_dias_semana`, `tipo_rutina`, `genero`, `nivel_experiencia`, `es_publico`, `fecha_creacion`) VALUES
(1, 1, 'mix pump', 'rutina de fuerza', NULL, NULL, 0.0, 0, 4, 'metodologica', 'unisex', 'principiante', 0, '2025-12-28 02:39:20'),
(2, 2, 'power gym', 'rutina de fuerza', NULL, NULL, 0.0, 0, 4, 'metodologica', 'unisex', 'principiante', 0, '2025-12-28 02:48:59'),
(3, 2, 'mix pump2', 'rutina de fuerza', NULL, NULL, 0.0, 0, 4, 'metodologica', 'unisex', 'principiante', 0, '2025-12-28 02:57:55'),
(4, 2, 'ful body 4 días', 'rutina todo el cuerpo', NULL, NULL, 0.0, 0, 4, 'metodologica', 'unisex', 'principiante', 0, '2025-12-28 03:11:11'),
(5, 2, 'mix pump', 'rutina fuerza', NULL, NULL, 0.0, 0, 3, 'metodologica', 'unisex', 'principiante', 0, '2025-12-28 03:20:43'),
(6, 2, 'mix pump', 'rutina fuerza', NULL, NULL, 0.0, 0, 4, 'metodologica', 'unisex', 'principiante', 0, '2025-12-28 04:02:20'),
(7, 1, 'mix pump', 'ljljk', NULL, NULL, 0.0, 0, 4, 'metodologica', 'unisex', 'principiante', 0, '2025-12-28 05:12:03'),
(8, 2, 'mix pump', 'rutina alterna', NULL, NULL, 0.0, 0, 4, 'metodologica', 'unisex', 'principiante', 0, '2025-12-28 18:58:00'),
(9, 2, 'mix pump', 'adsfa', 'torso y piernas', '', 0.0, 0, 4, 'metodologica', 'unisex', 'principiante', 0, '2025-12-28 20:12:03');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `rutinas_asignadas`
--

CREATE TABLE `rutinas_asignadas` (
  `id` int(11) NOT NULL,
  `rutina_id` int(11) NOT NULL,
  `entrenador_id` int(11) NOT NULL,
  `cliente_id` int(11) NOT NULL,
  `fecha_asignacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `activo` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `sesiones_activas`
--

CREATE TABLE `sesiones_activas` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `session_id` varchar(255) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `ultima_actividad` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `sesiones_ejercicio`
--

CREATE TABLE `sesiones_ejercicio` (
  `id` int(11) NOT NULL,
  `ejercicio_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `num_sesion` int(11) NOT NULL,
  `num_serie` int(11) NOT NULL,
  `peso` decimal(6,2) DEFAULT NULL,
  `unidad_peso` enum('kg','lb') DEFAULT 'kg',
  `repeticiones` int(11) DEFAULT NULL,
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_realizado` date DEFAULT NULL COMMENT 'Fecha en que se realizó el entrenamiento'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `nombre_completo` varchar(100) NOT NULL,
  `ciudad` varchar(50) NOT NULL,
  `usuario` varchar(50) NOT NULL,
  `contrasena` varchar(255) NOT NULL,
  `tipo_usuario` enum('standard','premium','premium_pro','admin') DEFAULT 'standard',
  `plan_pro` varchar(20) DEFAULT NULL COMMENT 'starter, professional, unlimited',
  `max_clientes` int(11) DEFAULT NULL COMMENT 'Límite de clientes según plan',
  `fecha_vencimiento_plan` date DEFAULT NULL COMMENT 'Fecha de vencimiento del plan premium',
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp(),
  `activo` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `nombre_completo`, `ciudad`, `usuario`, `contrasena`, `tipo_usuario`, `plan_pro`, `max_clientes`, `fecha_vencimiento_plan`, `fecha_registro`, `activo`) VALUES
(1, 'Administrador', 'Sistema', 'admin', '$2y$10$UPK5RRCNlUHtdtLD.cIT2u7BELHx2twET3j9SmpT0dWWbxZI9KDsq', 'premium_pro', 'unlimited', 0, '2035-12-27', '2025-12-28 00:28:17', 1),
(2, 'Willito mansilla poma', 'abancay', 'P3rchy', '$2y$10$ozq4l.uQE0jMuKsQk9suH.B6QRxRo6LqO3k3UcdyC2YqAFC137E4a', 'standard', NULL, NULL, NULL, '2025-12-28 00:52:52', 1);

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `biblioteca_ejercicios`
--
ALTER TABLE `biblioteca_ejercicios`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`),
  ADD KEY `categoria_id` (`categoria_id`);

--
-- Indices de la tabla `calificaciones_rutinas`
--
ALTER TABLE `calificaciones_rutinas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `usuario_rutina` (`usuario_id`,`rutina_id`),
  ADD KEY `rutina_id` (`rutina_id`);

--
-- Indices de la tabla `categorias_ejercicios`
--
ALTER TABLE `categorias_ejercicios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nombre` (`nombre`);

--
-- Indices de la tabla `contenido_aprendizaje`
--
ALTER TABLE `contenido_aprendizaje`
  ADD PRIMARY KEY (`id`),
  ADD KEY `modulo_id` (`modulo_id`),
  ADD KEY `creador_id` (`creador_id`);

--
-- Indices de la tabla `dias_rutina`
--
ALTER TABLE `dias_rutina`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unico_dia_rutina` (`rutina_id`,`dia_semana`);

--
-- Indices de la tabla `ejercicios`
--
ALTER TABLE `ejercicios`
  ADD PRIMARY KEY (`id`),
  ADD KEY `dia_rutina_id` (`dia_rutina_id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Indices de la tabla `ejercicios_basicos`
--
ALTER TABLE `ejercicios_basicos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `rutina_id` (`rutina_id`);

--
-- Indices de la tabla `entrenador_clientes`
--
ALTER TABLE `entrenador_clientes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `entrenador_cliente_unico` (`entrenador_id`,`cliente_id`),
  ADD KEY `cliente_id` (`cliente_id`);

--
-- Indices de la tabla `evaluaciones_serie`
--
ALTER TABLE `evaluaciones_serie`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unica_evaluacion` (`ejercicio_id`,`usuario_id`,`num_sesion`,`num_serie`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Indices de la tabla `historial_precios`
--
ALTER TABLE `historial_precios`
  ADD PRIMARY KEY (`id`),
  ADD KEY `plan_precio_id` (`plan_precio_id`),
  ADD KEY `usuario_modifico` (`usuario_modifico`);

--
-- Indices de la tabla `metricas_clientes`
--
ALTER TABLE `metricas_clientes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `entrenador_id` (`entrenador_id`),
  ADD KEY `idx_cliente_fecha` (`cliente_id`,`fecha_registro`);

--
-- Indices de la tabla `modulos_aprendizaje`
--
ALTER TABLE `modulos_aprendizaje`
  ADD PRIMARY KEY (`id`),
  ADD KEY `creador_id` (`creador_id`);

--
-- Indices de la tabla `notas_sesion`
--
ALTER TABLE `notas_sesion`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unica_nota_sesion` (`ejercicio_id`,`usuario_id`,`num_sesion`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Indices de la tabla `notificaciones`
--
ALTER TABLE `notificaciones`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_usuario_leido` (`usuario_id`,`leido`);

--
-- Indices de la tabla `planes_precios`
--
ALTER TABLE `planes_precios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `plan_duracion` (`plan_id`,`duracion_meses`);

--
-- Indices de la tabla `planes_pro`
--
ALTER TABLE `planes_pro`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nombre` (`nombre`);

--
-- Indices de la tabla `rutinas`
--
ALTER TABLE `rutinas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Indices de la tabla `rutinas_asignadas`
--
ALTER TABLE `rutinas_asignadas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `rutina_id` (`rutina_id`),
  ADD KEY `entrenador_id` (`entrenador_id`),
  ADD KEY `cliente_id` (`cliente_id`);

--
-- Indices de la tabla `sesiones_activas`
--
ALTER TABLE `sesiones_activas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `session_id` (`session_id`),
  ADD KEY `usuario_id` (`usuario_id`),
  ADD KEY `idx_ultima_actividad` (`ultima_actividad`);

--
-- Indices de la tabla `sesiones_ejercicio`
--
ALTER TABLE `sesiones_ejercicio`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unica_serie` (`ejercicio_id`,`usuario_id`,`num_sesion`,`num_serie`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `usuario` (`usuario`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `biblioteca_ejercicios`
--
ALTER TABLE `biblioteca_ejercicios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de la tabla `calificaciones_rutinas`
--
ALTER TABLE `calificaciones_rutinas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `categorias_ejercicios`
--
ALTER TABLE `categorias_ejercicios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT de la tabla `contenido_aprendizaje`
--
ALTER TABLE `contenido_aprendizaje`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `dias_rutina`
--
ALTER TABLE `dias_rutina`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT de la tabla `ejercicios`
--
ALTER TABLE `ejercicios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de la tabla `ejercicios_basicos`
--
ALTER TABLE `ejercicios_basicos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `entrenador_clientes`
--
ALTER TABLE `entrenador_clientes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `evaluaciones_serie`
--
ALTER TABLE `evaluaciones_serie`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `historial_precios`
--
ALTER TABLE `historial_precios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `metricas_clientes`
--
ALTER TABLE `metricas_clientes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `modulos_aprendizaje`
--
ALTER TABLE `modulos_aprendizaje`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `notas_sesion`
--
ALTER TABLE `notas_sesion`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `notificaciones`
--
ALTER TABLE `notificaciones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `planes_precios`
--
ALTER TABLE `planes_precios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de la tabla `planes_pro`
--
ALTER TABLE `planes_pro`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `rutinas`
--
ALTER TABLE `rutinas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de la tabla `rutinas_asignadas`
--
ALTER TABLE `rutinas_asignadas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `sesiones_activas`
--
ALTER TABLE `sesiones_activas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `sesiones_ejercicio`
--
ALTER TABLE `sesiones_ejercicio`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `biblioteca_ejercicios`
--
ALTER TABLE `biblioteca_ejercicios`
  ADD CONSTRAINT `biblioteca_ejercicios_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `biblioteca_ejercicios_ibfk_2` FOREIGN KEY (`categoria_id`) REFERENCES `categorias_ejercicios` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `calificaciones_rutinas`
--
ALTER TABLE `calificaciones_rutinas`
  ADD CONSTRAINT `calificaciones_rutinas_ibfk_1` FOREIGN KEY (`rutina_id`) REFERENCES `rutinas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `calificaciones_rutinas_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `contenido_aprendizaje`
--
ALTER TABLE `contenido_aprendizaje`
  ADD CONSTRAINT `contenido_aprendizaje_ibfk_1` FOREIGN KEY (`modulo_id`) REFERENCES `modulos_aprendizaje` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `contenido_aprendizaje_ibfk_2` FOREIGN KEY (`creador_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `dias_rutina`
--
ALTER TABLE `dias_rutina`
  ADD CONSTRAINT `dias_rutina_ibfk_1` FOREIGN KEY (`rutina_id`) REFERENCES `rutinas` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `ejercicios`
--
ALTER TABLE `ejercicios`
  ADD CONSTRAINT `ejercicios_ibfk_1` FOREIGN KEY (`dia_rutina_id`) REFERENCES `dias_rutina` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `ejercicios_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `ejercicios_basicos`
--
ALTER TABLE `ejercicios_basicos`
  ADD CONSTRAINT `ejercicios_basicos_ibfk_1` FOREIGN KEY (`rutina_id`) REFERENCES `rutinas` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `entrenador_clientes`
--
ALTER TABLE `entrenador_clientes`
  ADD CONSTRAINT `entrenador_clientes_ibfk_1` FOREIGN KEY (`entrenador_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `entrenador_clientes_ibfk_2` FOREIGN KEY (`cliente_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `evaluaciones_serie`
--
ALTER TABLE `evaluaciones_serie`
  ADD CONSTRAINT `evaluaciones_serie_ibfk_1` FOREIGN KEY (`ejercicio_id`) REFERENCES `ejercicios` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `evaluaciones_serie_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `historial_precios`
--
ALTER TABLE `historial_precios`
  ADD CONSTRAINT `historial_precios_ibfk_1` FOREIGN KEY (`plan_precio_id`) REFERENCES `planes_precios` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `historial_precios_ibfk_2` FOREIGN KEY (`usuario_modifico`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `metricas_clientes`
--
ALTER TABLE `metricas_clientes`
  ADD CONSTRAINT `metricas_clientes_ibfk_1` FOREIGN KEY (`cliente_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `metricas_clientes_ibfk_2` FOREIGN KEY (`entrenador_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `modulos_aprendizaje`
--
ALTER TABLE `modulos_aprendizaje`
  ADD CONSTRAINT `modulos_aprendizaje_ibfk_1` FOREIGN KEY (`creador_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `notas_sesion`
--
ALTER TABLE `notas_sesion`
  ADD CONSTRAINT `notas_sesion_ibfk_1` FOREIGN KEY (`ejercicio_id`) REFERENCES `ejercicios` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `notas_sesion_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `notificaciones`
--
ALTER TABLE `notificaciones`
  ADD CONSTRAINT `notificaciones_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `planes_precios`
--
ALTER TABLE `planes_precios`
  ADD CONSTRAINT `planes_precios_ibfk_1` FOREIGN KEY (`plan_id`) REFERENCES `planes_pro` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `rutinas`
--
ALTER TABLE `rutinas`
  ADD CONSTRAINT `rutinas_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `rutinas_asignadas`
--
ALTER TABLE `rutinas_asignadas`
  ADD CONSTRAINT `rutinas_asignadas_ibfk_1` FOREIGN KEY (`rutina_id`) REFERENCES `rutinas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `rutinas_asignadas_ibfk_2` FOREIGN KEY (`entrenador_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `rutinas_asignadas_ibfk_3` FOREIGN KEY (`cliente_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `sesiones_activas`
--
ALTER TABLE `sesiones_activas`
  ADD CONSTRAINT `sesiones_activas_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `sesiones_ejercicio`
--
ALTER TABLE `sesiones_ejercicio`
  ADD CONSTRAINT `sesiones_ejercicio_ibfk_1` FOREIGN KEY (`ejercicio_id`) REFERENCES `ejercicios` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `sesiones_ejercicio_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
