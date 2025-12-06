SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

USE vr_mall;


-- 聊天紀錄資料表
CREATE TABLE `chat_messages` (
  `message_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `session_id` varchar(255) DEFAULT NULL,
  `sender` enum('user','admin') NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;

-- 預設聊天紀錄資料
INSERT INTO `chat_messages` (`message_id`, `user_id`, `session_id`, `sender`, `message`, `is_read`, `created_at`) VALUES
(1, 2, NULL, 'user', '你好', 0, '2025-12-01 07:39:01'),
(2, 1, NULL, 'user', '我有問題需要幫助', 0, '2025-12-01 07:50:17'),
(3, 2, NULL, 'admin', '是的，有甚麼能幫助您的嗎?', 1, '2025-12-01 07:50:26'),
(4, 2, NULL, 'user', '我的訂單有問題，等了很久都還沒到', 0, '2025-12-01 07:53:34'),
(5, 2, NULL, 'admin', '請您稍等，現在就幫您查看', 1, '2025-12-01 07:55:24');


-- 會員資料表
CREATE TABLE `members` (
  `member_id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `role` enum('member','admin') NOT NULL DEFAULT 'member',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;

-- 預設會員資料
INSERT INTO `members` (`member_id`, `email`, `password_hash`, `name`, `phone`, `role`, `created_at`) VALUES
(1, '123@gmail.com', SHA2('123', 256), 'Tom', '0987655341', 'admin', '2025-11-24 06:51:29'),
(2, '456@gmail.com', SHA2('456', 256), 'John', '0965548121', 'member', '2025-11-24 06:51:53');


-- 會員地址資料表
CREATE TABLE `member_addresses` (
  `address_id` int(11) NOT NULL,
  `member_id` int(11) NOT NULL,
  `recipient_name` varchar(255) NOT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `address_line` varchar(500) NOT NULL,
  `is_default` tinyint(1) DEFAULT 0
) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;

-- 預設地址資料
INSERT INTO `member_addresses` (`address_id`, `member_id`, `recipient_name`, `phone`, `address_line`, `is_default`) VALUES
(1, 2, 'John', '0965548121', '台中市北屯區文心路四段800號', 1);


-- 訂單資料表
CREATE TABLE `orders` (
  `order_id` int(11) NOT NULL,
  `member_id` int(11) NOT NULL,
  `address_id` int(11) DEFAULT NULL,
  `total_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `status` enum('pending','preparing','shipping','done') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;

-- 預設訂單資料
INSERT INTO `orders` (`order_id`, `member_id`, `address_id`, `total_amount`, `status`, `created_at`) VALUES
(1, 2, 1, 299.00, 'pending', '2025-11-29 15:29:20'),
(2, 2, 1, 199.00, 'pending', '2025-12-01 08:01:51');


-- 訂單項目資料表
CREATE TABLE `order_items` (
  `order_item_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `unit_price` decimal(10,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;

-- 預設訂單明細資料
INSERT INTO `order_items` (`order_item_id`, `order_id`, `product_id`, `quantity`, `unit_price`) VALUES
(1, 1, 4, 1, 299.00),
(2, 2, 5, 1, 199.00);


-- 商品資料表
CREATE TABLE `products` (
  `product_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `category` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `stock` int(11) NOT NULL DEFAULT 0,
  `image_url` varchar(500) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`product_id`)
) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;

-- 預設商品資料
INSERT INTO `products` (`name`, `category`, `description`, `price`, `stock`, `image_url`, `is_active`) VALUES
('統一飲冰室 冰紅茶', '飲料', '台灣超商銷售量前幾名的即飲紅茶', 25.00, 200, '../image/1.jpg', 1),
('御茶園 特撰日式綠茶', '飲料', '無糖茶類人氣商品', 25.00, 200, '../image/2.jpg', 1),
('可口可樂', '飲料', '最常見碳酸飲料，適合搭配零食', 35.00, 200, '../image/3.jpg', 1),
('雪碧', '飲料', '檸檬萊姆口味碳酸飲料', 35.00, 200, '../image/4.jpg', 1),
('伯朗咖啡 藍山風味', '飲料', '台灣知名罐裝咖啡，超商常態商品', 35.00, 150, '../image/5.jpg', 1),
('貝納頌咖啡 經典曼特寧風味', '飲料', '濃厚咖啡風味，外帶需求高', 39.00, 120, '../image/6.jpg', 1),
('多力多滋玉米片 超濃起司', '零食', '玉米片類零食長期上架商品', 49.00, 140, '../image/7.jpg', 1),
('乖乖 奶油椰子口味', '零食', '台灣國民零食，便利商店長期銷售', 35.00, 140, '../image/8.jpg', 1),
('卡廸那 洋芋片牛排口味', '零食', '經典洋芋片口味，熱銷多年', 39.00, 150, '../image/9.jpg', 1),
('統一科學麵', '食品', '台灣經典點心麵', 12.00, 300, '../image/10.jpg', 1),
('來一客 鮮蝦魚板杯麵', '食品', '單杯沖泡麵品常態販售', 45.00, 200, '../image/11.jpg', 1),
('滿漢大餐 蔥燒牛肉麵', '食品', '泡麵高階款代表', 65.00, 200, '../image/12.jpg', 1),
('義美 特濃巧克力口味小泡芙', '甜點', '台灣最熱銷點心之一', 45.00, 140, '../image/13.jpg', 1),
('義美 純麥取向蘇打餅乾', '零食', '健康型蘇打餅乾商品', 42.00, 120, '../image/14.jpg', 1),
('哈根達斯迷你杯 香草', '冰品', '便利店固定陳列款口味', 95.00, 60, '../image/15.jpg', 1),
('哈根達斯迷你杯 草莓', '冰品', '人氣果香迷你杯款', 95.00, 60, '../image/16.jpg', 1);