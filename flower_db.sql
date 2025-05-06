-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 06, 2025
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
-- Table structure for table `message`
--

CREATE TABLE `message` (
  `id` int(11) NOT NULL,
  `user_id` bigint(20) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `number` varchar(30) NOT NULL,
  `message` text NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
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
-- Triggers `orders`
--
DELIMITER $$
CREATE TRIGGER `before_insert_order_id` BEFORE INSERT ON `orders` FOR EACH ROW
BEGIN
    DECLARE date_prefix CHAR(6);
    DECLARE order_count INT;
    DECLARE new_id BIGINT;

    SET date_prefix = DATE_FORMAT(NEW.placed_on, '%d%m%y');
    SELECT COUNT(*) + 1 INTO order_count
    FROM orders
    WHERE DATE(placed_on) = DATE(NEW.placed_on);
    SET new_id = CONCAT(date_prefix, LPAD(order_count, 4, '0')) + 0;
    SET NEW.id = new_id;
END$$
DELIMITER ;
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
-- Triggers `users`
--
DELIMITER $$
CREATE TRIGGER `before_insert_user_id` BEFORE INSERT ON `users` FOR EACH ROW
BEGIN
    DECLARE date_prefix CHAR(6);
    DECLARE user_count INT;
    DECLARE new_id BIGINT;

    SET date_prefix = DATE_FORMAT(NOW(), '%d%m%y');
    SELECT COUNT(*) + 1 INTO user_count
    FROM users
    WHERE DATE(created_at) = CURDATE();
    SET new_id = CONCAT(date_prefix, LPAD(user_count, 4, '0')) + 0;
    SET NEW.id = new_id;
END$$
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
-- Indexes for table `message`
--
ALTER TABLE `message`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `message`
--
ALTER TABLE `message`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `order_status_log`
--
ALTER TABLE `order_status_log`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wishlist`
--
ALTER TABLE `wishlist`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

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
-- Constraints for table `message`
--
ALTER TABLE `message`
  ADD CONSTRAINT `message_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

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
-- Constraints for table `wishlist`
--
ALTER TABLE `wishlist`
  ADD CONSTRAINT `wishlist_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `wishlist_ibfk_2` FOREIGN KEY (`pid`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
-- 1. Insert into users (3 users: 2 customers, 1 admin)
INSERT INTO users (name, email, password, status, user_type, created_at, updated_at)
VALUES
    ('Nguyen Van A', 'user1@gmail.com', '$2y$10$mBYFC.xN9ZWuVMjt9hSTLuP6X3YEX7x2jAAGE6wrxTtDb19aYyk/G', 'Offline', 'user', '2025-01-12 08:00:00', '2025-01-12 08:00:00'),
    ('Tran Thi B', 'user2@gmail.com', '$2y$10$mBYFC.xN9ZWuVMjt9hSTLuP6X3YEX7x2jAAGE6wrxTtDb19aYyk/G', 'Offline', 'user', '2025-01-12 09:00:00', '2025-01-12 09:00:00'),
    ('Admin', 'admin@gmail.com', '$2y$10$mBYFC.xN9ZWuVMjt9hSTLuP6X3YEX7x2jAAGE6wrxTtDb19aYyk/G', 'Offline', 'admin', '2025-01-12 10:00:00', '2025-01-12 10:00:00');
-- IDs: 1201250001, 1201250002, 1201250003 (generated by trigger)

-- 2. Insert into products (3 products: bouquet, basket, wedding flowers)
INSERT INTO products (id, name, price, sale, product_detail, image, origin, type, created_at, updated_at)
VALUES
    (1, 'Rose Bouquet', 25.00, 0.00, 'A beautiful bouquet of 12 red roses.', 'rose_bouquet.jpg', 'Vietnam', 'bouquet', '2025-01-12 07:00:00', '2025-01-12 07:00:00'),
    (2, 'Lily Basket', 40.00, 5.00, 'A basket of white lilies and greenery.', 'lily_basket.jpg', 'Netherlands', 'basket', '2025-01-12 07:00:00', '2025-01-12 07:00:00'),
    (3, 'Wedding Arrangement', 100.00, 0.00, 'Elegant floral arrangement for weddings.', 'wedding_arrangement.jpg', 'France', 'wedding', '2025-01-12 07:00:00', '2025-01-12 07:00:00');

-- 3. Insert into cart (2 items in cart for user1 and user2)
INSERT INTO cart (user_id, pid, name, price, quantity, image, created_at, updated_at)
VALUES
    (1201250001, 1, 'Rose Bouquet', 25.00, 2, 'rose_bouquet.jpg', '2025-01-12 08:30:00', '2025-01-12 08:30:00'),
    (1201250002, 2, 'Lily Basket', 40.00, 1, 'lily_basket.jpg', '2025-01-12 09:30:00', '2025-01-12 09:30:00');

-- 4. Insert into wishlist (1 item in wishlist for user1)
INSERT INTO wishlist (user_id, pid, name, price, image, created_at, updated_at)
VALUES
    (1201250001, 3, 'Wedding Arrangement', 100.00, 'wedding_arrangement.jpg', '2025-01-12 08:45:00', '2025-01-12 08:45:00');

-- 5. Insert into message (1 message from user1)
INSERT INTO message (user_id, name, email, number, message, created_at)
VALUES
    (1201250001, 'Nguyen Van A', 'user1@gmail.com', '0901234567', 'Can you customize a bouquet for my birthday?', '2025-01-12 08:50:00');

-- 6. Insert into orders (2 orders for user1 and user2)
INSERT INTO orders (user_id, name, number, email, method, address, total_products, total_price, placed_on, payment_status, admin_approval)
VALUES
    (1201250001, 'Nguyen Van A', '0901234567', 'user1@gmail.com', 'COD', '123 Hanoi St, Hanoi', 'Rose Bouquet x2', 50.00, '2025-01-12 09:00:00', 'pending', 'pending'),
    (1201250002, 'Tran Thi B', '0907654321', 'user2@gmail.com', 'Bank Transfer', '456 Saigon St, HCMC', 'Lily Basket x1', 40.00, '2025-01-12 10:00:00', 'confirmed', 'approved');
-- IDs: 1201250001, 1201250002 (generated by trigger)

-- 7. Insert into order_status_log (2 logs for orders)
INSERT INTO order_status_log (order_id, old_payment_status, new_payment_status, old_approval_status, new_approval_status, changed_at)
VALUES
    (1201250001, NULL, 'pending', NULL, 'pending', '2025-01-12 09:00:00'),
    (1201250002, 'pending', 'confirmed', 'pending', 'approved', '2025-01-12 10:30:00');
