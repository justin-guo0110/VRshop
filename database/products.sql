-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- 主機： 127.0.0.1
-- 產生時間： 2026-03-25 07:50:04
-- 伺服器版本： 10.4.32-MariaDB
-- PHP 版本： 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- 資料庫： `vr_mall`
--

-- --------------------------------------------------------

--
-- 資料表結構 `products`
--

CREATE TABLE `products` (
  `product_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `category` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `stock` int(11) NOT NULL DEFAULT 0,
  `image_url` varchar(500) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- 傾印資料表的資料 `products`
--

INSERT INTO `products` (`product_id`, `name`, `category`, `description`, `price`, `stock`, `image_url`, `is_active`) VALUES
(1, 'VR Headset Pro', 'Electronics', 'High-end VR headset with 4K display.', 599.00, 20, 'image/1.jpg', 1),
(2, 'Motion Controller', 'Accessories', 'Precision motion controller for immersive experiences.', 129.00, 50, 'image/2.jpg', 1),
(3, 'VR Ready PC', 'Computers', 'Powerful PC optimized for VR gaming.', 1499.00, 10, 'image/3.jpg', 1),
(4, '360 Camera', 'Cameras', 'Capture 360-degree videos and photos.', 299.00, 30, 'image/4.jpg', 1),
(5, 'VR Game Pack', 'Games', '', 199.00, 20, 'image/5.jpg', 1),
(6, '雪碧', '', '', 20.00, 0, 'image/6.jpg', 1),
(7, '123', '1231', '', 123.00, 123, 'image/7.jpg', 1),
(8, '1', '11', '', 1.00, 1, 'image/8.jpg', 1),
(9, '111', '111', '1234567898765432123456789', 111.00, 111, 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQAAAQABAAD/2wCEAAkGBxATEhITEhMWFRUWGB4aFxgYGBgaGBUXGRcdGBcVGBUZHyggGxomHhcVITEhJyktLi8wFyAzODMtNygtLisBCgoKDg0OGxAQGzglICUyLS0tLS0tLS0tKy0vLS0vLi0vLS8tLy0tLy0tLS0tLS0tLS0tLS0tLS0tLS8tLS0tLf/AABEIAOEA4QMBEQACEQEDEQH/xAAbAAEAAgMBAQAAAAAAAAAAAAAABAUCAwYBB//EAEsQAAEEAAQCBgUHCQYEBwEAAAEAAgMRBBIhMQVBBhMiUWGRMnGBodEXIzNCVLHwBxRSYoKSssHSFRYkcpPhRHOi8UNTY3SDlMI0/8QAGgEBAAMBAQEAAAAAAAAAAAAAAAECAwQFBv/EADQRAAIBAgMFBgYCAwEBAQAAAAABAgMREiExBBNBUWEUcYGRofAFIjKx0eFCUiMz8', 1),
(10, '餅乾', '1', '', 100.00, 8, '', 1);

--
-- 已傾印資料表的索引
--

--
-- 資料表索引 `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`product_id`);

--
-- 在傾印的資料表使用自動遞增(AUTO_INCREMENT)
--

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `products`
--
ALTER TABLE `products`
  MODIFY `product_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
