-- Database: my_shop_db
CREATE DATABASE IF NOT EXISTS `my_shop_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `my_shop_db`;

-- Table: products (CRUD target)
DROP TABLE IF EXISTS `order_items`;
DROP TABLE IF EXISTS `orders`;
DROP TABLE IF EXISTS `products`;

CREATE TABLE `products` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL,
  `category` VARCHAR(100) NOT NULL,
  `price` DECIMAL(10, 2) NOT NULL,
  `stock` INT NOT NULL DEFAULT 10,
  `description` TEXT NOT NULL,
  `image` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: orders (Mini Checkout simulation)
CREATE TABLE `orders` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `customer_name` VARCHAR(255) NOT NULL,
  `customer_email` VARCHAR(255) NOT NULL,
  `customer_phone` VARCHAR(20) NOT NULL,
  `address` TEXT NOT NULL,
  `total_amount` DECIMAL(10, 2) NOT NULL,
  `status` VARCHAR(50) DEFAULT 'Pending',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: order_items
CREATE TABLE `order_items` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `order_id` INT NOT NULL,
  `product_id` INT NOT NULL,
  `product_name` VARCHAR(255) NOT NULL,
  `price` DECIMAL(10, 2) NOT NULL,
  `quantity` INT NOT NULL,
  `subtotal` DECIMAL(10, 2) NOT NULL,
  FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed initial products
INSERT INTO `products` (`name`, `category`, `price`, `stock`, `description`, `image`) VALUES
('Wireless Noise-Canceling Headphones', 'Electronics', 2499.00, 15, 'Premium over-ear wireless Bluetooth headphones with deep bass and active noise cancellation.', 'p1_headphones.svg'),
('Smart Fitness Watch 2.0', 'Electronics', 1899.00, 25, 'Waterproof smartwatch with continuous heart-rate monitor, SpO2 sensor, and 7-day battery life.', 'p2_smartwatch.svg'),
('Casual Denim Jacket', 'Clothing', 1299.00, 18, 'Classic slim-fit washed blue denim jacket made from 100% durable premium cotton.', 'p3_jacket.svg'),
('Running Sports Sneakers', 'Footwear', 1499.00, 12, 'Lightweight breathable mesh athletic running shoes with superior shock absorption sole.', 'p4_shoes.svg'),
('Minimalist Leather Backpack', 'Accessories', 999.00, 20, 'Water-resistant stylish laptop backpack suitable for daily office, commute, and college use.', 'p5_backpack.svg'),
('Mechanical Gaming Keyboard', 'Electronics', 1999.00, 8, 'RGB backlit mechanical keyboard with blue tactile switches, aluminum frame, and anti-ghosting.', 'p6_keyboard.svg');
