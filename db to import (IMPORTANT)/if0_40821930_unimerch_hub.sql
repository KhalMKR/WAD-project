-- phpMyAdmin SQL Dump
-- version 4.9.0.1
-- https://www.phpmyadmin.net/
--
-- Host: sql109.infinityfree.com
-- Generation Time: Jan 04, 2026 at 08:00 AM
-- Server version: 11.4.9-MariaDB
-- PHP Version: 7.2.22

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `if0_40821930_unimerch_hub`
--

-- --------------------------------------------------------

--
-- Table structure for table `cart`
--

CREATE TABLE `cart` (
  `cartItemID` int(11) NOT NULL,
  `userID` int(11) NOT NULL,
  `productID` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `dateAdded` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cart`
--

INSERT INTO `cart` (`cartItemID`, `userID`, `productID`, `quantity`, `dateAdded`) VALUES
(52, 6, 2, 1, '2026-01-04 12:23:36');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `orderID` int(11) NOT NULL,
  `userID` int(11) NOT NULL,
  `totalAmount` decimal(10,2) NOT NULL,
  `orderDate` timestamp NOT NULL DEFAULT current_timestamp(),
  `fullName` varchar(200) NOT NULL DEFAULT '',
  `phone` varchar(50) NOT NULL DEFAULT '',
  `address` varchar(400) NOT NULL DEFAULT '',
  `paymentMethod` varchar(100) NOT NULL DEFAULT '',
  `orderNumber` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`orderID`, `userID`, `totalAmount`, `orderDate`, `fullName`, `phone`, `address`, `paymentMethod`, `orderNumber`) VALUES
(1, 3, '40.00', '2025-12-23 00:36:28', 'khal', '011-29180129', 'kuching', 'Bank Transfer', 'UMH-000001'),
(2, 3, '30.00', '2025-12-23 00:44:58', 'khal', '011-21231311', 'Betong', 'Card', 'UMH-000002'),
(5, 3, '30.00', '2025-12-30 12:33:16', 'Khal', '011-98798722', 'PermyJaya', 'E-Wallet', 'UMH-000005'),
(6, 3, '30.00', '2025-12-30 12:33:21', 'Khal', '011-98798722', 'PermyJaya', 'E-Wallet', 'UMH-000006'),
(7, 3, '75.00', '2025-12-30 12:46:05', 'Piqzhar', '019-86779976', 'Taman Gamelan', 'Card', 'UMH-000007'),
(8, 3, '50.00', '2025-12-30 14:45:24', 'Ejen Ali', '019-145134142', 'Batu 7', 'Bank Transfer', 'UMH-000008'),
(9, 3, '30.00', '2025-12-31 10:48:55', 'khal', '012-3456789', 'Batu 8', 'Bank Transfer', 'UMH-000009'),
(10, 5, '85.00', '2026-01-04 09:48:38', 'Khalish', '011-29180129', 'Batu Kitang', 'Bank Transfer', 'UMH-000010'),
(11, 5, '15.00', '2026-01-04 09:58:50', 'Khalish', '012-12358305', 'Kuching', 'Card', 'UMH-000011'),
(12, 5, '90.00', '2026-01-04 10:09:40', 'Khalish', '011-29180129', 'Betong', 'Bank Transfer', 'UMH-000012'),
(13, 5, '15.00', '2026-01-04 12:14:51', 'Khalish', '011-12312312', 'Betong', 'Bank Transfer', 'UMH-AGA76D'),
(14, 6, '230.00', '2026-01-04 12:17:00', 'AFIQ ZHARFAN BIN ZAIDIN', '0198840977', 'LOT 5348, LRG 6, JLN DATO PERMAISURI 3C', 'Bank Transfer', 'UMH-7P8XME'),
(15, 7, '155.00', '2026-01-04 12:25:36', 'chung lik chiann', '0137771542', 'HSE 19, LOT 30, LORONG NOUNTUN JUTA 1', 'Bank Transfer', 'UMH-YAMLVD'),
(16, 5, '10.00', '2026-01-04 12:30:41', 'Khalish', '011-29180129', 'Kuching', 'Bank Transfer', 'UMH-KH5USL'),
(17, 5, '140.00', '2026-01-04 20:51:59', 'Khalish', '011-12312312', 'Betong', 'E-Wallet', 'UMH-LXKXU6'),
(18, 5, '15.00', '2026-01-05 04:54:54', 'Khalish', '012-3456789', 'Batu Kitang', 'Bank Transfer', 'UMH-AK8X6K'),
(19, 5, '35.00', '2026-01-05 04:57:24', 'Khalish', '011-98798722', 'Batu 7', 'E-Wallet', 'UMH-H3X75X'),
(20, 5, '100.00', '2026-01-05 04:58:38', 'Khalish', '011-29180129', 'Kuching', 'E-Wallet', 'UMH-B59VNP');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `itemID` int(11) NOT NULL,
  `orderID` int(11) NOT NULL,
  `productID` int(11) DEFAULT NULL,
  `name` varchar(200) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `quantity` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`itemID`, `orderID`, `productID`, `name`, `price`, `quantity`) VALUES
(1, 1, 3, 'UniMerch Cap', '30.00', 1),
(2, 1, 4, 'Bracelet', '10.00', 1),
(3, 2, 3, 'UniMerch Cap', '30.00', 1),
(6, 5, 4, 'Bracelet', '10.00', 3),
(7, 6, 4, 'Bracelet', '10.00', 3),
(8, 7, 3, 'UniMerch Cap', '30.00', 2),
(9, 7, 2, 'FCSIT Lanyard', '15.00', 1),
(10, 8, 4, 'Bracelet', '10.00', 2),
(11, 8, 3, 'UniMerch Cap', '30.00', 1),
(12, 9, 3, 'UniMerch Cap', '30.00', 1),
(13, 10, 1, 'UNIMAS Hoodie', '85.00', 1),
(14, 11, 2, 'FCSIT Lanyard', '15.00', 1),
(15, 12, 3, 'UniMerch Cap', '30.00', 3),
(17, 14, 1, 'UNIMAS Hoodie', '85.00', 1),
(20, 13, 2, 'FCSIT Lanyard', '15.00', 1),
(21, 14, 1, 'UNIMAS Hoodie', '85.00', 2),
(22, 14, 7, 'FCSIT T-shirt', '35.00', 1),
(23, 14, 4, 'Bracelet', '10.00', 1),
(24, 14, 2, 'FCSIT Lanyard', '15.00', 1),
(25, 15, 7, 'FCSIT T-shirt', '35.00', 2),
(26, 15, 1, 'UNIMAS Hoodie', '85.00', 1),
(27, 16, 4, 'Bracelet', '10.00', 1),
(28, 17, 8, 'Unimerch Backpack', '100.00', 1),
(29, 17, 4, 'Bracelet', '10.00', 1),
(30, 17, 3, 'UniMerch Cap', '30.00', 1),
(31, 18, 2, 'FCSIT Lanyard', '15.00', 1),
(32, 19, 7, 'FCSIT T-shirt', '35.00', 1),
(33, 20, 8, 'Unimerch Backpack', '100.00', 1);

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `productID` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `category` varchar(225) NOT NULL,
  `imagePath` varchar(225) NOT NULL,
  `stockQuantity` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`productID`, `name`, `price`, `category`, `imagePath`, `stockQuantity`) VALUES
(1, 'UNIMAS Hoodie', '85.00', 'Clothing', 'assets/images/hoodie.png', 50),
(2, 'FCSIT Lanyard', '15.00', 'Accessories', 'assets/images/lanyard.png', 100),
(3, 'UniMerch Cap', '30.00', 'Clothing', 'assets/images/cap.png', 50),
(4, 'Bracelet', '10.00', 'Accessory', 'assets/images/bracelet.png', 50),
(7, 'FCSIT T-shirt', '35.00', 'Clothing', 'assets/images/1767525266_Gemini_Generated_Image_4wdy9v4wdy9v4wdy.png', 20),
(8, 'Unimerch Backpack', '100.00', 'Accessories', 'assets/images/1767529340_Gemini_Generated_Image_eketegeketegeket.png', 30);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `userID` int(11) NOT NULL,
  `fullName` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(225) NOT NULL,
  `userType` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`userID`, `fullName`, `email`, `password`, `userType`) VALUES
(1, 'Admin', 'admin@unimhub.com', '$2y$10$FUG2SXSlNiggezw6AaTScOtMnItMWC4sNNPg0GiXGc1WM33nBd.7q', 'admin'),
(3, 'khal', 'khal@mail.com', '$2y$10$E0h9ul.hQLtz2fh/3qzI6e3bFj/bsLbbnRpXwUHaF6PoO5v3AT3uq', 'user'),
(5, 'Khalish', 'muhdkhalishreeza@gmail.com', '$2y$10$duDyQUQrIq7WWeMUVN4j3Oj0tIqWZjap6jbOQMLvkqC9GWsFRd4L2', 'user'),
(6, 'AFIQ ZHARFAN BIN ZAIDIN', 'afiqzharfan24@gmail.com', '$2y$10$KANDs8mHvtfPoPminGobtO.TZ.TcS.eWMfGIQwnrSR/9o2hNLR0oW', 'user'),
(7, 'chung lik chiann', 'cchunglik@gmail.com', '$2y$10$AgoNvrBSRFltD7ZFE62wCuneeQ3p2RdYXOXjfWWG9sW1pWKpEJhvK', 'user');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `cart`
--
ALTER TABLE `cart`
  ADD PRIMARY KEY (`cartItemID`),
  ADD UNIQUE KEY `unique_user_product` (`userID`,`productID`),
  ADD KEY `idx_cart_userID` (`userID`),
  ADD KEY `productID` (`productID`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`orderID`),
  ADD UNIQUE KEY `orderNumber` (`orderNumber`),
  ADD KEY `userID` (`userID`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`itemID`),
  ADD KEY `orderID` (`orderID`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`productID`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD UNIQUE KEY `userID` (`userID`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `cart`
--
ALTER TABLE `cart`
  MODIFY `cartItemID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=80;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `itemID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `productID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `userID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `cart`
--
ALTER TABLE `cart`
  ADD CONSTRAINT `cart_ibfk_1` FOREIGN KEY (`userID`) REFERENCES `users` (`userID`) ON DELETE CASCADE,
  ADD CONSTRAINT `cart_ibfk_2` FOREIGN KEY (`productID`) REFERENCES `products` (`productID`) ON DELETE CASCADE,
  ADD CONSTRAINT `cart_ibfk_3` FOREIGN KEY (`userID`) REFERENCES `users` (`userID`) ON DELETE CASCADE,
  ADD CONSTRAINT `cart_ibfk_4` FOREIGN KEY (`productID`) REFERENCES `products` (`productID`) ON DELETE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`userID`) REFERENCES `users` (`userID`) ON DELETE CASCADE;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `fk_order_items_order` FOREIGN KEY (`orderID`) REFERENCES `orders` (`orderID`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
