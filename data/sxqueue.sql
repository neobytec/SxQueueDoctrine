-- phpMyAdmin SQL Dump
-- version 4.2.7.1
-- http://www.phpmyadmin.net
--
-- Servidor: localhost
-- Tiempo de generación: 03-10-2014 a las 17:08:57
-- Versión del servidor: 5.6.20
-- Versión de PHP: 5.5.15

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Base de datos: `fashion`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `sxqueue`
--

CREATE TABLE IF NOT EXISTS `sxqueue` (
`id` int(11) NOT NULL,
  `queue` varchar(64) NOT NULL,
  `data` text NOT NULL,
  `status` smallint(1) NOT NULL,
  `date_created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `date_scheduled` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `date_executed` timestamp NULL DEFAULT NULL,
  `date_finished` timestamp NULL DEFAULT NULL,
  `date_failed` timestamp NULL DEFAULT NULL,
  `attempts` int(10) unsigned NOT NULL DEFAULT '0',
  `message` text,
  `trace` text
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=7 ;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `sxqueue`
--
ALTER TABLE `sxqueue`
 ADD PRIMARY KEY (`id`), ADD KEY `status` (`status`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `sxqueue`
--
ALTER TABLE `sxqueue`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=7;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
