CREATE DATABASE IF NOT EXISTS vr_mall
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE vr_mall;

-- 會員資料表
CREATE TABLE IF NOT EXISTS members (
  member_id INT AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(255) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  name VARCHAR(255) NOT NULL,
  phone VARCHAR(50),
  role ENUM('member','admin') NOT NULL DEFAULT 'member',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 會員地址資料表
CREATE TABLE IF NOT EXISTS member_addresses (
  address_id INT AUTO_INCREMENT PRIMARY KEY,
  member_id INT NOT NULL,
  recipient_name VARCHAR(255) NOT NULL,
  phone VARCHAR(50),
  address_line VARCHAR(500) NOT NULL,
  is_default TINYINT(1) DEFAULT 0,
  FOREIGN KEY (member_id) REFERENCES members(member_id) ON DELETE CASCADE
);

-- 商品資料表
CREATE TABLE IF NOT EXISTS products (
  product_id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  category VARCHAR(100),
  description TEXT,
  price DECIMAL(10,2) NOT NULL DEFAULT 0,
  stock INT NOT NULL DEFAULT 0,
  image_url VARCHAR(500),
  is_active TINYINT(1) NOT NULL DEFAULT 1
);

-- 訂單資料表
CREATE TABLE IF NOT EXISTS orders (
  order_id INT AUTO_INCREMENT PRIMARY KEY,
  member_id INT NOT NULL,
  address_id INT,
  total_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
  status ENUM('pending','preparing','shipping','done') NOT NULL DEFAULT 'pending',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (member_id) REFERENCES members(member_id) ON DELETE CASCADE,
  FOREIGN KEY (address_id) REFERENCES member_addresses(address_id) ON DELETE SET NULL
);

-- 訂單項目資料表
CREATE TABLE IF NOT EXISTS order_items (
  order_item_id INT AUTO_INCREMENT PRIMARY KEY,
  order_id INT NOT NULL,
  product_id INT NOT NULL,
  quantity INT NOT NULL DEFAULT 1,
  unit_price DECIMAL(10,2) NOT NULL DEFAULT 0,
  FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE CASCADE,
  FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE RESTRICT
);

-- 聊天紀錄資料表
CREATE TABLE IF NOT EXISTS chat_messages (
  message_id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT DEFAULT NULL,
  session_id VARCHAR(255) DEFAULT NULL,
  sender ENUM('user','admin') NOT NULL,
  message TEXT NOT NULL,
  is_read TINYINT(1) DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);


-- 預設會員資料
INSERT INTO members (email, password_hash, name, phone, role)
VALUES
('123@gmail.com', SHA2('123', 256), 'Tom', '0987655341', 'admin'),
('456@gmail.com', SHA2('456', 256), 'John', '0965548121', 'member');

-- 預設地址資料
INSERT INTO member_addresses (member_id, recipient_name, phone, address_line, is_default)
VALUES
(2, 'John', '0965548121', '台中市北屯區文心路四段800號', 1);

-- 預設商品資料
INSERT INTO products (name, category, description, price, stock, image_url, is_active)
VALUES
('統一飲冰室 冰紅茶 600ml', '飲料', '台灣超商銷售量前幾名的即飲紅茶', 25.00, 200, '', 1),
('御茶園 日式綠茶 600ml', '飲料', '無糖茶類人氣商品', 25.00, 200, '', 1),
('可口可樂 600ml', '飲料', '最常見碳酸飲料，適合搭配零食', 35.00, 200, '', 1),
('雪碧 600ml', '飲料', '檸檬萊姆口味碳酸飲料', 35.00, 200, '', 1),
('伯朗咖啡 藍山罐 240ml', '飲料', '台灣知名罐裝咖啡，超商常態商品', 35.00, 150, '', 1),
('光泉鮮奶 390ml', '乳品', '鮮奶類人氣商品，小瓶裝', 38.00, 120, '', 1),
('貝納頌曼特寧咖啡 罐裝', '飲料', '濃厚咖啡風味，外帶需求高', 39.00, 120, '', 1),
('多力多滋 起司口味 65g', '零食', '玉米片類零食長期上架商品', 49.00, 140, '', 1),
('乖乖 奶油椰子 50g', '零食', '台灣國民零食，便利商店長期銷售', 35.00, 140, '', 1),
('卡迪那 洋芋片 原味 60g', '零食', '經典洋芋片口味，熱銷多年', 39.00, 150, '', 1),
('統一科學麵', '食品', '台灣經典點心麵', 12.00, 300, '', 1),
('來一客 鮮蝦魚板杯麵', '食品', '單杯沖泡麵品常態販售', 45.00, 200, '', 1),
('滿漢大餐 蔥燒牛肉麵', '食品', '泡麵高階款代表', 65.00, 200, '', 1),
('義美小泡芙 巧克力口味', '甜點', '台灣最熱銷點心之一', 45.00, 140, '', 1),
('義美全麥蘇打餅乾', '零食', '健康型蘇打餅乾商品', 42.00, 120, '', 1),
('哈根達斯迷你杯 香草', '冰品', '便利店固定陳列款口味', 95.00, 60, '', 1),
('哈根達斯迷你杯 草莓', '冰品', '人氣果香迷你杯款', 95.00, 60, '', 1);


-- 初始聊天紀錄
INSERT INTO chat_messages (user_id, session_id, sender, message, is_read, created_at)
VALUES
(2, NULL, 'user', '我有訂單方面的問題', 0, '2025-12-01 07:53:34'),
(1, NULL, 'admin', '你好，有甚麼能幫助您的嗎?', 1, '2025-12-01 07:55:24');
