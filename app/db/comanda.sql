-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 19, 2023 at 04:36 AM
-- Server version: 10.4.27-MariaDB
-- PHP Version: 8.2.0

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `comanda`
--

-- --------------------------------------------------------

--
-- Table structure for table `estado_mesas`
--

CREATE TABLE `estado_mesas` (
  `id` int(11) NOT NULL,
  `estado` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `estado_mesas`
--

INSERT INTO `estado_mesas` (`id`, `estado`) VALUES
(1, 'esperando pedido'),
(2, 'comiendo'),
(3, 'pagando'),
(4, 'cerrada'),
(5, 'libre');

-- --------------------------------------------------------

--
-- Table structure for table `estado_pedidos`
--

CREATE TABLE `estado_pedidos` (
  `id` int(11) NOT NULL,
  `estado` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `estado_pedidos`
--

INSERT INTO `estado_pedidos` (`id`, `estado`) VALUES
(1, 'pendiente'),
(2, 'en preparacion'),
(3, 'listo para entregar'),
(4, 'entregado');

-- --------------------------------------------------------

--
-- Table structure for table `mesas`
--

CREATE TABLE `mesas` (
  `id` int(11) NOT NULL,
  `codigo_mesa` text NOT NULL,
  `estado` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `mesas`
--

INSERT INTO `mesas` (`id`, `codigo_mesa`, `estado`) VALUES
(1, 'wygdp', '1'),
(2, 'NjmEN', NULL),
(3, 'uMvdm', NULL),
(4, 'CCOh6', NULL),
(5, 'M8JeM', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `pedidos`
--

CREATE TABLE `pedidos` (
  `id` int(11) NOT NULL,
  `codigoPedido` varchar(5) NOT NULL,
  `fotoMesa` varchar(150) DEFAULT NULL,
  `idMesa` int(8) NOT NULL,
  `idProducto` int(11) NOT NULL,
  `cantidadProducto` int(11) NOT NULL,
  `tipoProducto` text NOT NULL,
  `nombreCliente` varchar(50) NOT NULL,
  `estado` varchar(50) NOT NULL,
  `fecha` datetime NOT NULL,
  `tiempoEstimado` text DEFAULT NULL,
  `tiempoInicio` time DEFAULT NULL,
  `tiempoEntregado` time DEFAULT NULL,
  `fechaBaja` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pedidos`
--

INSERT INTO `pedidos` (`id`, `codigoPedido`, `fotoMesa`, `idMesa`, `idProducto`, `cantidadProducto`, `tipoProducto`, `nombreCliente`, `estado`, `fecha`, `tiempoEstimado`, `tiempoInicio`, `tiempoEntregado`, `fechaBaja`) VALUES
(1, 'AAAA1', NULL, 1, 1, 1, 'cerveza', 'Pepe', '1', '2023-11-17 18:52:14', NULL, NULL, NULL, NULL),
(2, 'AAAA1', NULL, 1, 4, 1, 'comida', 'Pepe', '1', '2023-11-17 18:52:14', NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `productos`
--

CREATE TABLE `productos` (
  `id` int(11) NOT NULL,
  `descripcion` varchar(50) NOT NULL,
  `tipo` int(11) NOT NULL,
  `precio` float NOT NULL,
  `fechaAlta` date NOT NULL,
  `fechaBaja` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `productos`
--

INSERT INTO `productos` (`id`, `descripcion`, `tipo`, `precio`, `fechaAlta`, `fechaBaja`) VALUES
(1, 'Cerveza Negra', 1, 100, '0000-00-00', NULL),
(2, 'Vino tinto', 2, 100, '2023-11-13', NULL),
(3, 'Vino blanco', 2, 100, '2023-11-13', NULL),
(4, 'Alfajor', 3, 100, '2023-11-16', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `tipo_producto`
--

CREATE TABLE `tipo_producto` (
  `codigo` int(11) NOT NULL,
  `tipo` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tipo_producto`
--

INSERT INTO `tipo_producto` (`codigo`, `tipo`) VALUES
(1, 'cerveza'),
(2, 'bebida'),
(3, 'comida');

-- --------------------------------------------------------

--
-- Table structure for table `tipo_usuario`
--

CREATE TABLE `tipo_usuario` (
  `tipo` text NOT NULL,
  `codigo` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tipo_usuario`
--

INSERT INTO `tipo_usuario` (`tipo`, `codigo`) VALUES
('bartender', 1),
('cervecero', 2),
('cocinero', 3),
('mozo', 4),
('socio', 5);

-- --------------------------------------------------------

--
-- Table structure for table `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `tipo` int(11) NOT NULL,
  `user` text NOT NULL,
  `alta` date DEFAULT NULL,
  `baja` date DEFAULT NULL,
  `password` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `usuarios`
--

INSERT INTO `usuarios` (`id`, `tipo`, `user`, `alta`, `baja`, `password`) VALUES
(1, 5, 'Pepe', '2023-11-02', NULL, 'abc'),
(2, 5, 'Pepe Peposo', '2023-11-05', NULL, 'abc'),
(3, 2, 'Roberto Robertoso', '2023-11-06', NULL, 'abc'),
(4, 5, 'El Socio Anonimo', '2023-11-13', NULL, 'abc'),
(5, 5, 'Ruperto', '2023-11-17', NULL, 'abc'),
(6, 5, 'TEST', '2023-11-17', NULL, 'abc');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `estado_mesas`
--
ALTER TABLE `estado_mesas`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `estado_pedidos`
--
ALTER TABLE `estado_pedidos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `id` (`id`);

--
-- Indexes for table `mesas`
--
ALTER TABLE `mesas`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `pedidos`
--
ALTER TABLE `pedidos`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `productos`
--
ALTER TABLE `productos`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tipo_producto`
--
ALTER TABLE `tipo_producto`
  ADD PRIMARY KEY (`codigo`);

--
-- Indexes for table `tipo_usuario`
--
ALTER TABLE `tipo_usuario`
  ADD PRIMARY KEY (`codigo`);

--
-- Indexes for table `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `estado_pedidos`
--
ALTER TABLE `estado_pedidos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `tipo_producto`
--
ALTER TABLE `tipo_producto`
  MODIFY `codigo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `tipo_usuario`
--
ALTER TABLE `tipo_usuario`
  MODIFY `codigo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
