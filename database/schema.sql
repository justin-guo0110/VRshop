CREATE DATABASE IF NOT EXISTS vr_mall CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE vr_mall;

CREATE TABLE IF NOT EXISTS members (
  member_id INT AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(255) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  name VARCHAR(255) NOT NULL,
  phone VARCHAR(50),
  role ENUM('member','admin') NOT NULL DEFAULT 'member',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS member_addresses (
  address_id INT AUTO_INCREMENT PRIMARY KEY,
  member_id INT NOT NULL,
  recipient_name VARCHAR(255) NOT NULL,
  phone VARCHAR(50),
  address_line VARCHAR(500) NOT NULL,
  is_default TINYINT(1) DEFAULT 0,
  FOREIGN KEY (member_id) REFERENCES members(member_id) ON DELETE CASCADE
);

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

CREATE TABLE IF NOT EXISTS order_items (
  order_item_id INT AUTO_INCREMENT PRIMARY KEY,
  order_id INT NOT NULL,
  product_id INT NOT NULL,
  quantity INT NOT NULL DEFAULT 1,
  unit_price DECIMAL(10,2) NOT NULL DEFAULT 0,
  FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE CASCADE,
  FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE RESTRICT
);

INSERT INTO members (email, password_hash, name, phone, role)
VALUES ('admin@example.com', SHA2('admin123', 256), 'Admin', '', 'admin');

INSERT INTO products (name, category, description, price, stock, image_url, is_active) VALUES
('VR Headset Pro', 'Electronics', 'High-end VR headset with 4K display.', 599.00, 20, 'images/vr1.jpg', 1),
('Motion Controller', 'Accessories', 'Precision motion controller for immersive experiences.', 129.00, 50, 'images/controller.jpg', 1),
('VR Ready PC', 'Computers', 'Powerful PC optimized for VR gaming.', 1499.00, 10, 'images/pc.jpg', 1),
('360 Camera', 'Cameras', 'Capture 360-degree videos and photos.', 299.00, 30, 'images/camera.jpg', 1),
('VR Game Pack', 'Games', 'Bundle of top-rated VR games.', 199.00, 100, 'images/games.jpg', 1);
