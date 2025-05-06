-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 06, 2025 at 03:23 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `flower_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `cart`
--

CREATE TABLE `cart` (
  `id` int(11) NOT NULL,
  `user_id` bigint(20) NOT NULL,
  `pid` bigint(20) NOT NULL,
  `name` varchar(255) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `quantity` int(11) NOT NULL,
  `image` varchar(255) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` bigint(20) NOT NULL,
  `user_id` bigint(20) NOT NULL,
  `name` varchar(255) NOT NULL,
  `number` varchar(30) NOT NULL,
  `email` varchar(255) NOT NULL,
  `method` varchar(255) NOT NULL,
  `address` varchar(255) NOT NULL,
  `total_products` text NOT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `placed_on` datetime NOT NULL DEFAULT current_timestamp(),
  `payment_status` enum('pending','confirmed','completed') NOT NULL DEFAULT 'pending',
  `admin_approval` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `name`, `number`, `email`, `method`, `address`, `total_products`, `total_price`, `placed_on`, `payment_status`, `admin_approval`, `created_at`, `updated_at`) VALUES
(605250001, 605250001, 'John Doe', '0123456789', 'john@example.com', 'COD', '123 Elm Street', 'Red Roses x2, Wedding Flowers x1', 150.00, '2025-05-06 13:05:59', 'pending', 'pending', '2025-05-06 13:05:59', '2025-05-06 13:05:59'),
(605250002, 605250002, 'Jane Smith', '0987654321', 'jane@example.com', 'Bank Transfer', '456 Oak Street', 'Sunflower Basket x3', 120.00, '2025-05-06 13:05:59', 'confirmed', 'approved', '2025-05-06 13:05:59', '2025-05-06 13:05:59');

--
-- Triggers `orders`
--
DELIMITER $$
CREATE TRIGGER `after_order_insert_clear_cart` AFTER INSERT ON `orders` FOR EACH ROW BEGIN
    DELETE FROM cart WHERE user_id = NEW.user_id;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `after_order_status_update` AFTER UPDATE ON `orders` FOR EACH ROW BEGIN
    IF OLD.payment_status != NEW.payment_status OR OLD.admin_approval != NEW.admin_approval THEN
        INSERT INTO order_status_log (order_id, old_payment_status, new_payment_status, old_approval_status, new_approval_status, changed_at)
        VALUES (NEW.id, OLD.payment_status, NEW.payment_status, OLD.admin_approval, NEW.admin_approval, NOW());
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `before_insert_order_id` BEFORE INSERT ON `orders` FOR EACH ROW BEGIN
    DECLARE date_prefix CHAR(6);
    DECLARE last_seq INT;
    DECLARE new_id BIGINT;

    SET date_prefix = DATE_FORMAT(NEW.placed_on, '%d%m%y');
    SELECT COALESCE(MAX(CAST(SUBSTRING(id, 7, 4) AS UNSIGNED)), 0) + 1 INTO last_seq
    FROM orders
    WHERE DATE(placed_on) = DATE(NEW.placed_on);
    SET new_id = CONCAT(date_prefix, LPAD(last_seq, 4, '0')) + 0;
    SET NEW.id = new_id;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `order_status_log`
--

CREATE TABLE `order_status_log` (
  `log_id` int(11) NOT NULL,
  `order_id` bigint(20) NOT NULL,
  `old_payment_status` enum('pending','confirmed','completed') DEFAULT NULL,
  `new_payment_status` enum('pending','confirmed','completed') NOT NULL,
  `old_approval_status` enum('pending','approved','rejected') DEFAULT NULL,
  `new_approval_status` enum('pending','approved','rejected') NOT NULL,
  `changed_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_status_log`
--

INSERT INTO `order_status_log` (`log_id`, `order_id`, `old_payment_status`, `new_payment_status`, `old_approval_status`, `new_approval_status`, `changed_at`) VALUES
(1, 605250002, 'pending', 'confirmed', 'pending', 'approved', '2025-05-06 13:05:59');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` bigint(20) NOT NULL,
  `name` varchar(255) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `sale` decimal(10,2) NOT NULL DEFAULT 0.00,
  `product_detail` text NOT NULL,
  `image` varchar(255) NOT NULL,
  `origin` varchar(255) DEFAULT NULL,
  `type` enum('birthday','wedding','bouquet','condolence','basket','other') DEFAULT 'other',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `name`, `price`, `sale`, `product_detail`, `image`, `origin`, `type`, `created_at`, `updated_at`) VALUES
(1, 'Red Roses', 25.00, 5.00, 'A classic bouquet of red roses', 'red_roses.jpg', 'Vietnam', 'bouquet', '2025-05-06 13:05:59', '2025-05-06 13:05:59'),
(2, 'Wedding Flowers', 100.00, 15.00, 'Elegant wedding bouquet', 'wedding_flowers.jpg', 'France', 'wedding', '2025-05-06 13:05:59', '2025-05-06 13:05:59'),
(3, 'Sunflower Basket', 40.00, 10.00, 'A sunny basket of sunflowers', 'sunflowers.jpg', 'Thailand', 'basket', '2025-05-06 13:05:59', '2025-05-06 13:05:59'),
(4, 'Condolence Flowers', 50.00, 0.00, 'White lilies for condolences', 'condolence.jpg', 'Vietnam', 'condolence', '2025-05-06 13:05:59', '2025-05-06 13:05:59');

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `id` int(11) NOT NULL,
  `user_id` bigint(20) NOT NULL,
  `product_id` bigint(20) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `number` varchar(30) NOT NULL,
  `message` text NOT NULL,
  `rating` int(1) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reviews`
--

INSERT INTO `reviews` (`id`, `user_id`, `product_id`, `name`, `email`, `number`, `message`, `rating`, `created_at`) VALUES
(1, 605250001, 1, 'John Doe', 'john@example.com', '0123456789', 'Beautiful flowers, very fresh!', 5, '2025-05-06 13:05:59'),
(2, 605250002, 3, 'Jane Smith', 'jane@example.com', '0987654321', 'Bright and cheerful basket, loved it!', 4, '2025-05-06 13:05:59');

-- --------------------------------------------------------

--
-- Table structure for table `review_replies`
--

CREATE TABLE `review_replies` (
  `id` int(11) NOT NULL,
  `review_id` int(11) NOT NULL,
  `admin_id` bigint(20) NOT NULL,
  `reply` text NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `review_replies`
--

INSERT INTO `review_replies` (`id`, `review_id`, `admin_id`, `reply`, `created_at`) VALUES
(1, 1, 605250003, 'Thank you for your positive feedback!', '2025-05-06 13:05:59'),
(2, 2, 605250003, 'We appreciate your review, thank you!', '2025-05-06 13:05:59');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` bigint(20) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `status` enum('Online','Offline') NOT NULL DEFAULT 'Offline',
  `user_type` enum('user','admin') NOT NULL DEFAULT 'user',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `status`, `user_type`, `created_at`, `updated_at`) VALUES
(605250001, 'John Doe', 'john@example.com', '$2y$10$mBYFC.xN9ZWuVMjt9hSTLuP6X3YEX7x2jAAGE6wrxTtDb19aYyk/G', 'Online', 'user', '2025-05-06 13:05:59', '2025-05-06 20:09:46'),
(605250002, 'Jane Smith', 'jane@example.com', '$2y$10$mBYFC.xN9ZWuVMjt9hSTLuP6X3YEX7x2jAAGE6wrxTtDb19aYyk/G', 'Offline', 'user', '2025-05-06 13:05:59', '2025-05-06 20:09:51'),
(605250003, 'Admin User', 'admin@example.com', '$2y$10$mBYFC.xN9ZWuVMjt9hSTLuP6X3YEX7x2jAAGE6wrxTtDb19aYyk/G', 'Online', 'admin', '2025-05-06 13:05:59', '2025-05-06 20:22:23');

--
-- Triggers `users`
--
DELIMITER $$
CREATE TRIGGER `before_insert_user_id` BEFORE INSERT ON `users` FOR EACH ROW BEGIN
    DECLARE date_prefix CHAR(6);
    DECLARE last_seq INT;
    DECLARE new_id BIGINT;

    SET date_prefix = DATE_FORMAT(NOW(), '%d%m%y');
    SELECT COALESCE(MAX(CAST(SUBSTRING(id, 7, 4) AS UNSIGNED)), 0) + 1 INTO last_seq
    FROM users
    WHERE DATE(created_at) = CURDATE();
    SET new_id = CONCAT(date_prefix, LPAD(last_seq, 4, '0')) + 0;
    SET NEW.id = new_id;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `wishlist`
--

CREATE TABLE `wishlist` (
  `id` int(11) NOT NULL,
  `user_id` bigint(20) NOT NULL,
  `pid` bigint(20) NOT NULL,
  `name` varchar(255) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `image` varchar(255) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `wishlist`
--

INSERT INTO `wishlist` (`id`, `user_id`, `pid`, `name`, `price`, `image`, `created_at`, `updated_at`) VALUES
(1, 605250001, 3, 'Sunflower Basket', 40.00, 'sunflowers.jpg', '2025-05-06 13:05:59', '2025-05-06 13:05:59'),
(2, 605250002, 1, 'Red Roses', 25.00, 'red_roses.jpg', '2025-05-06 13:05:59', '2025-05-06 13:05:59');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `cart`
--
ALTER TABLE `cart`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`,`pid`),
  ADD KEY `pid` (`pid`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `order_status_log`
--
ALTER TABLE `order_status_log`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `order_id` (`order_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `review_replies`
--
ALTER TABLE `review_replies`
  ADD PRIMARY KEY (`id`),
  ADD KEY `review_id` (`review_id`),
  ADD KEY `admin_id` (`admin_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `wishlist`
--
ALTER TABLE `wishlist`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`,`pid`),
  ADD KEY `pid` (`pid`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `cart`
--
ALTER TABLE `cart`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `order_status_log`
--
ALTER TABLE `order_status_log`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `review_replies`
--
ALTER TABLE `review_replies`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `wishlist`
--
ALTER TABLE `wishlist`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `cart`
--
ALTER TABLE `cart`
  ADD CONSTRAINT `cart_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `cart_ibfk_2` FOREIGN KEY (`pid`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `order_status_log`
--
ALTER TABLE `order_status_log`
  ADD CONSTRAINT `order_status_log_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `review_replies`
--
ALTER TABLE `review_replies`
  ADD CONSTRAINT `review_replies_ibfk_1` FOREIGN KEY (`review_id`) REFERENCES `reviews` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `review_replies_ibfk_2` FOREIGN KEY (`admin_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `wishlist`
--
ALTER TABLE `wishlist`
  ADD CONSTRAINT `wishlist_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `wishlist_ibfk_2` FOREIGN KEY (`pid`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
