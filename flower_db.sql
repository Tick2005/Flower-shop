-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 11, 2025 at 12:53 PM
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

--
-- Dumping data for table `cart`
--

INSERT INTO `cart` (`id`, `user_id`, `pid`, `name`, `price`, `quantity`, `image`, `created_at`, `updated_at`) VALUES
(3, 605250002, 21, 'White Lily Bridal Bouquet', 90.00, 1, '21.jpg', '2025-05-10 10:10:00', '2025-05-10 10:10:00'),
(4, 605250002, 31, 'Red Rose Bouquet', 65.00, 3, '31.jpg', '2025-05-10 10:15:00', '2025-05-10 10:15:00');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` bigint(20) NOT NULL,
  `user_id` bigint(20) NOT NULL,
  `product_id` bigint(20) NOT NULL,
  `name` varchar(255) NOT NULL,
  `number` varchar(30) NOT NULL,
  `email` varchar(255) NOT NULL,
  `method` varchar(255) NOT NULL,
  `address` varchar(255) NOT NULL,
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

INSERT INTO `orders` (`id`, `user_id`, `product_id`, `name`, `number`, `email`, `method`, `address`, `total_price`, `placed_on`, `payment_status`, `admin_approval`, `created_at`, `updated_at`) VALUES
(605250001, 605250001, 1, 'John Doe', '0123456789', 'john@example.com', 'COD', '123 Elm Street', 150.00, '2025-05-06 13:05:59', 'pending', 'pending', '2025-05-06 13:05:59', '2025-05-11 16:41:09'),
(605250002, 605250002, 2, 'Jane Smith', '0987654321', 'jane@example.com', 'Bank Transfer', '456 Oak Street', 120.00, '2025-05-06 13:05:59', 'confirmed', 'approved', '2025-05-06 13:05:59', '2025-05-11 16:41:13'),
(1105250001, 605250001, 11, 'Duong', '0338756467', 'username@gmail.com', 'COD', 'xxxx', 85.00, '2025-05-11 17:49:24', 'pending', 'pending', '2025-05-11 17:49:24', '2025-05-11 17:49:24'),
(1105250002, 605250001, 15, 'Duong', '0338756467', 'username@gmail.com', 'COD', 'xxxx', 130.00, '2025-05-11 17:49:24', 'pending', 'pending', '2025-05-11 17:49:24', '2025-05-11 17:49:24'),
(1105250003, 605250001, 3, 'Phan Văn Dương', '0338756467', '523h0017@student.tdtu.edu.vn', 'COD', 'xxxx', 79.00, '2025-05-11 17:51:44', 'pending', 'pending', '2025-05-11 17:51:44', '2025-05-11 17:51:44');

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
(1, 'Lavender Fan Arrangement', 95.00, 10.00, 'Artistic flower basket in purple, yellow, and green tones, featuring fan-shaped decorations.', '1.jpg', 'Vietnam', 'other', '2025-05-10 13:54:32', '2025-05-10 13:57:40'),
(2, 'Succulent Lady Planter', 72.00, 12.00, 'Succulent arrangement in a charming lady-head ceramic pot. Great for desks and gifts.', '2.jpg', 'Thailand', 'other', '2025-05-10 13:54:32', '2025-05-10 13:58:35'),
(3, 'Blue Delight Bouquet', 79.00, 0.00, 'Elegant bouquet in soft blue and cream tones, decorated with delicate white butterflies.', '3.jpg', 'Vietnam', 'basket', '2025-05-10 13:54:32', '2025-05-10 14:06:01'),
(4, 'Peach Garden in Box', 82.00, 14.00, 'Transparent floral box with peach roses, daisies, and mixed blooms. Natural and stylish.', '4.jpg', 'Russia', 'other', '2025-05-10 13:54:32', '2025-05-10 17:17:19'),
(5, 'Teddy Pink Gift Box', 86.00, 25.00, 'Pastel pink flower box with a teddy bear, perfect for birthdays and romantic gifts.', '5.jpg', 'Myanma', 'birthday', '2025-05-10 13:54:32', '2025-05-10 17:17:27'),
(6, 'Succulent Harmony Box', 80.00, 8.00, 'Assorted succulents arranged in a modern clear box. Adds freshness to any space.', '6.jpg', 'Vietnam', 'other', '2025-05-10 13:54:32', '2025-05-10 17:17:34'),
(7, 'Yellow Sunshine Box', 87.00, 0.00, 'Bright yellow and white flowers mixed with succulents in a cheerful clear box.', '7.jpg', 'Vietnam', 'wedding', '2025-05-10 13:54:32', '2025-05-10 17:17:45'),
(8, 'Pink Celebration Basket', 89.00, 8.00, 'Soft pink flower basket perfect for celebrations or birthdays.', '8.jpg', 'China', 'basket', '2025-05-10 13:54:32', '2025-05-10 17:17:51'),
(9, 'Pastel Romance Basket', 86.00, 18.00, 'Romantic flower basket in pastel pink and lavender tones, ideal for heartfelt gifts.', '9.jpg', 'Vietnam', 'basket', '2025-05-10 13:54:32', '2025-05-10 17:17:58'),
(10, 'Blue-Yellow Luxury Bouquet', 98.00, 11.00, 'Luxurious bouquet of blue and cream blooms, elegant and refined for special occasions.', '10.jpg', 'Paraguay', 'basket', '2025-05-10 13:54:32', '2025-05-10 17:18:04'),
(11, 'White Orchid Wreath', 85.00, 0.00, 'Elegant wreath with white orchids and roses, perfect for condolences.', '11.jpg', 'Thailand', 'wedding', '2025-05-10 10:00:00', '2025-05-10 14:11:56'),
(12, 'Yellow Daisy Wreath', 75.00, 0.00, 'Bright wreath with yellow daisies and white chrysanthemums for memorial services.', '12.jpg', 'Netherlands', 'basket', '2025-05-10 10:00:00', '2025-05-10 14:07:03'),
(13, 'Tropical Orange Basket', 95.00, 12.00, 'Vibrant basket with orange roses, orchids, and tropical flowers for celebrations.', '13.jpg', 'Brazil', 'basket', '2025-05-10 10:00:00', '2025-05-10 14:06:30'),
(14, 'Yellow Rose Wreath', 80.00, 0.00, 'Classic wreath with yellow roses and white blooms, ideal for funerals.', '14.jpg', 'Colombia', 'condolence', '2025-05-10 10:00:00', '2025-05-10 14:06:35'),
(15, 'Pink Rose Gift Bag', 65.00, 0.00, 'Charming pink bag bouquet with roses and carnations, great for birthdays.', '15.jpg', 'France', 'condolence', '2025-05-10 10:00:00', '2025-05-10 14:08:36'),
(16, 'White Daisy Bouquet', 45.00, 0.00, 'Simple bouquet of white daisies wrapped in kraft paper, perfect for any occasion.', '16.jpg', 'Germany', 'condolence', '2025-05-10 10:00:00', '2025-05-10 14:08:49'),
(17, 'White Chrysanthemum Stand', 90.00, 17.00, 'Elegant stand arrangement with white chrysanthemums and green leaves for memorials.', '17.jpg', 'Japan', 'condolence', '2025-05-10 10:00:00', '2025-05-10 14:09:00'),
(18, 'Yellow Mixed Wreath', 85.00, 0.00, 'Wreath with yellow roses, daisies, and white flowers, suitable for funerals.', '18.jpg', 'Ecuador', 'condolence', '2025-05-10 10:00:00', '2025-05-10 14:09:11'),
(19, 'Purple Rose Gift Bag', 70.00, 20.00, 'Stylish purple bag bouquet with roses and succulents, ideal for gifting.', '19.jpg', 'Australia', 'condolence', '2025-05-10 10:00:00', '2025-05-10 14:09:23'),
(20, 'Pink Chrysanthemum Wreath', 80.00, 0.00, 'Soft pink and white chrysanthemum wreath with black ribbons for condolences.', '20.jpg', 'South Africa', 'bouquet', '2025-05-10 10:00:00', '2025-05-10 14:09:37'),
(21, 'White Lily Bridal Bouquet', 90.00, 0.00, 'Elegant bridal bouquet with white lilies, perfect for weddings.', '21.jpg', 'Italy', 'bouquet', '2025-05-10 10:00:00', '2025-05-10 14:22:44'),
(22, 'Sunflower Kraft Bouquet', 55.00, 0.00, 'Rustic bouquet with a single sunflower and dark foliage, great for casual gifts.', '22.jpg', 'Spain', 'bouquet', '2025-05-10 10:00:00', '2025-05-10 10:00:00'),
(23, 'Blue Chrysanthemum Bouquet', 70.00, 14.00, 'Vibrant bouquet with blue chrysanthemums and white flowers for celebrations.', '23.jpg', 'China', 'wedding', '2025-05-10 10:00:00', '2025-05-10 14:25:23'),
(24, 'Pink Rose Handheld Bouquet', 60.00, 0.00, 'Delicate bouquet with pink roses and eucalyptus, ideal for birthdays.', '24.jpg', 'Kenya', 'birthday', '2025-05-10 10:00:00', '2025-05-10 10:00:00'),
(25, 'White Rose Wedding Bouquet', 85.00, 0.00, 'Classic wedding bouquet with white roses and green accents.', '25.jpg', 'Canada', 'wedding', '2025-05-10 10:00:00', '2025-05-10 10:00:00'),
(26, 'White Carnation Bridal Bouquet', 75.00, 7.00, 'Simple bridal bouquet with white carnations and greenery.', '26.jpg', 'Mexico', 'wedding', '2025-05-10 10:00:00', '2025-05-10 14:25:15'),
(27, 'Mixed Rose Stand Arrangement', 95.00, 0.00, 'Colorful stand with roses in pink, orange, and white for events.', '27.jpg', 'India', 'other', '2025-05-10 10:00:00', '2025-05-10 14:43:41'),
(28, 'White Orchid Wedding Cascade', 100.00, 20.00, 'Luxurious cascading bouquet with white orchids for weddings.', '28.jpg', 'Malaysia', 'wedding', '2025-05-10 10:00:00', '2025-05-10 14:25:10'),
(29, 'Pink Daisy Large Bouquet', 80.00, 15.00, 'Large bouquet with pink daisies and roses, perfect for gifting.', '29.jpg', 'Argentina', 'bouquet', '2025-05-10 10:00:00', '2025-05-10 14:25:03'),
(30, 'Sunflower Small Bouquet', 50.00, 0.00, 'Charming small bouquet with a sunflower and purple accents.', '30.jpg', 'Turkey', 'bouquet', '2025-05-10 10:00:00', '2025-05-10 10:00:00'),
(31, 'Red Rose Bouquet', 65.00, 0.00, 'Classic bouquet of vibrant red roses, perfect for romantic occasions.', '31.jpg', 'Peru', 'bouquet', '2025-05-10 10:00:00', '2025-05-10 10:00:00'),
(32, 'Blue Rose Bouquet', 70.00, 0.00, 'Unique bouquet with blue roses and babyâ€™s breath, ideal for special gifts.', '32.jpg', 'New Zealand', 'bouquet', '2025-05-10 10:00:00', '2025-05-10 10:00:00'),
(33, 'Pink Mixed Flower Bouquet', 75.00, 0.00, 'Charming bouquet with pink roses, daisies, and eucalyptus for celebrations.', '33.jpg', 'Vietnam', 'birthday', '2025-05-10 10:00:00', '2025-05-10 10:00:00'),
(34, 'White Lily Vase Arrangement', 60.00, 0.00, 'Elegant vase arrangement with white lilies, suitable for any occasion.', '34.jpg', 'Greece', 'other', '2025-05-10 10:00:00', '2025-05-10 14:43:34');

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
(1, 605250001, 11, 'John Doe', 'john@example.com', '123-456-7890', 'The wreath was beautiful and perfect for the occasion.', 5, '2025-05-10 12:00:00'),
(2, 605250001, 15, 'John Doe', 'john@example.com', '123-456-7890', 'Loved the pink roses, great for gifting!', 4, '2025-05-10 12:05:00'),
(3, 605250002, 21, 'Jane Smith', 'jane@example.com', '987-654-3210', 'Stunning bridal bouquet, made the day special.', 5, '2025-05-10 12:10:00'),
(4, 605250002, 31, 'Jane Smith', 'jane@example.com', '987-654-3210', 'Red roses were vibrant, but delivery was a bit late.', 3, '2025-05-10 12:15:00');

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
(3, 4, 605250003, 'Thank you for your kind words! Weâ€™re glad you liked the wreath.', '2025-05-10 14:54:03'),
(4, 4, 605250003, 'Thank you for your kind words! Weâ€™re glad you liked the wreath.', '2025-05-10 14:54:11'),
(5, 1, 605250003, 'Thank you for your kind words! Weâ€™re glad you liked the wreath.', '2025-05-10 12:05:00'),
(6, 2, 605250003, 'We appreciate your feedback! Happy you enjoyed the roses.', '2025-05-10 12:10:00'),
(7, 3, 605250003, 'Thank you! Weâ€™re honored to be part of your special day.', '2025-05-10 12:15:00'),
(8, 4, 605250003, 'Sorry for the delay, weâ€™ll work on improving our delivery. Thanks for the review!', '2025-05-10 12:20:00'),
(9, 1, 605250003, 'Oh! Thank you for your all respond for us!', '2025-05-10 15:02:05'),
(10, 2, 605250003, 'Oh! Love for respond so cute for you!', '2025-05-10 15:05:00'),
(11, 3, 605250003, 'Thank! See you later to soon!', '2025-05-10 15:06:40'),
(12, 3, 605250003, 'Thank for all!', '2025-05-10 15:07:26');

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
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `verification_code` varchar(32) DEFAULT NULL,
  `verified` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `status`, `user_type`, `created_at`, `updated_at`, `verification_code`, `verified`) VALUES
(605250001, 'John Doe', 'john@example.com', '$2y$10$mBYFC.xN9ZWuVMjt9hSTLuP6X3YEX7x2jAAGE6wrxTtDb19aYyk/G', 'Online', 'user', '2025-05-06 13:05:59', '2025-05-10 17:49:58', NULL, 0),
(605250002, 'Jane Smith', 'jane@example.com', '$2y$10$mBYFC.xN9ZWuVMjt9hSTLuP6X3YEX7x2jAAGE6wrxTtDb19aYyk/G', 'Offline', 'user', '2025-05-06 13:05:59', '2025-05-06 20:09:51', NULL, 0),
(605250003, 'Admin User', 'admin@example.com', '$2y$10$mBYFC.xN9ZWuVMjt9hSTLuP6X3YEX7x2jAAGE6wrxTtDb19aYyk/G', 'Offline', 'admin', '2025-05-06 13:05:59', '2025-05-10 15:55:39', NULL, 0);

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
  ADD KEY `user_id` (`user_id`),
  ADD KEY `orders_ibfk_2` (`product_id`);

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
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `cart`
--
ALTER TABLE `cart`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `order_status_log`
--
ALTER TABLE `order_status_log`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `review_replies`
--
ALTER TABLE `review_replies`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

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
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

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
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
