SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+08:00";

USE vr_mall;


-- 額外測試會員資料
INSERT IGNORE INTO `members` (`email`, `password_hash`, `name`, `phone`, `role`, `created_at`) VALUES
('admin@vrshop.com', '$2y$10$YIjlrBtos/Yh7gJ8p8Jdve9h8k7Q4R9s8M9o0P', '管理員', '139-0000-0001', 'admin', NOW()),
('vip@test.com', '$2y$10$N9qo8uLOickgx2ZMRZoMye', '張三', '138-1111-1111', 'member', DATE_SUB(NOW(), INTERVAL 60 DAY)),
('highvalue@test.com', '$2y$10$N9qo8uLOickgx2ZMRZoMye', '李四', '138-2222-2222', 'member', DATE_SUB(NOW(), INTERVAL 45 DAY)),
('new@test.com', '$2y$10$N9qo8uLOickgx2ZMRZoMye', '王五', '138-3333-3333', 'member', DATE_SUB(NOW(), INTERVAL 5 DAY)),
('regular@test.com', '$2y$10$N9qo8uLOickgx2ZMRZoMye', '趙六', '138-4444-4444', 'member', DATE_SUB(NOW(), INTERVAL 20 DAY)),
('churned@test.com', '$2y$10$N9qo8uLOickgx2ZMRZoMye', '孫七', '138-5555-5555', 'member', DATE_SUB(NOW(), INTERVAL 180 DAY));


-- 客戶標籤對應資料
INSERT IGNORE INTO `customer_label_mapping` (`member_id`, `label_id`, `assigned_at`) VALUES
(2, 1, NOW()),
(3, 2, NOW()),
(4, 3, NOW()),
(5, 4, NOW()),
(6, 5, NOW());


-- 促銷活動資料
INSERT INTO `promotions` (`title`, `description`, `promotion_type`, `discount_type`, `discount_value`, `min_amount`, `start_date`, `end_date`, `is_active`, `created_at`) VALUES
('春節大促88折', '全場產品春節促銷，享受88折優惠', 'global_discount', 'percent', 12, 0, DATE_SUB(NOW(), INTERVAL 5 DAY), DATE_ADD(NOW(), INTERVAL 10 DAY), 1, NOW()),
('滿599立減99', '購物滿599元立即減99元', 'threshold_discount', 'fixed', 99, 599, NOW(), DATE_ADD(NOW(), INTERVAL 30 DAY), 1, NOW()),
('VIP專享88折', 'VIP會員專享88折優惠', 'coupon', 'percent', 12, 100, NOW(), DATE_ADD(NOW(), INTERVAL 90 DAY), 1, NOW());


-- 優惠碼資料
INSERT INTO `promo_codes` (`promotion_id`, `code`, `usage_count`, `usage_limit`, `is_active`, `created_at`) VALUES
(1, 'SPRING2026', 2, 500, 1, NOW()),
(1, 'LUCKY88', 5, 300, 1, NOW()),
(2, 'SAVE99', 3, 1000, 1, NOW()),
(3, 'VIP88', 1, 100, 1, NOW())
ON DUPLICATE KEY UPDATE
  `usage_count` = VALUES(`usage_count`),
  `usage_limit` = VALUES(`usage_limit`),
  `is_active` = VALUES(`is_active`);


-- 展示用訂單資料
INSERT INTO `orders`
(`member_id`, `order_number`, `total_amount`, `discount_amount`, `shipping_fee`, `status`, `payment_method`, `ship_name`, `ship_phone`, `ship_address_line`, `created_at`, `updated_at`) VALUES
(2, 'ORD20260320001', 1200.00, 100.00, 10.00, 'done', 'credit_card', '張三', '138-1111-1111', '台北市信義區松壽路1號', DATE_SUB(NOW(), INTERVAL 20 DAY), DATE_SUB(NOW(), INTERVAL 19 DAY)),
(2, 'ORD20260321001', 599.00, 0.00, 10.00, 'done', 'credit_card', '張三', '138-1111-1111', '台北市信義區松壽路1號', DATE_SUB(NOW(), INTERVAL 15 DAY), DATE_SUB(NOW(), INTERVAL 14 DAY)),
(3, 'ORD20260322001', 899.00, 150.00, 10.00, 'shipping', 'paypal', '李四', '138-2222-2222', '台中市西屯區台灣大道三段99號', DATE_SUB(NOW(), INTERVAL 12 DAY), DATE_SUB(NOW(), INTERVAL 11 DAY)),
(4, 'ORD20260323001', 499.00, 0.00, 0.00, 'pending', 'credit_card', '王五', '138-3333-3333', '台南市東區中華東路一段100號', DATE_SUB(NOW(), INTERVAL 3 DAY), DATE_SUB(NOW(), INTERVAL 3 DAY)),
(5, 'ORD20260324001', 799.00, 50.00, 10.00, 'preparing', 'credit_card', '趙六', '138-4444-4444', '高雄市左營區博愛二路50號', DATE_SUB(NOW(), INTERVAL 2 DAY), DATE_SUB(NOW(), INTERVAL 2 DAY)),
(2, 'ORD20260325001', 1500.00, 200.00, 20.00, 'done', 'credit_card', '張三', '138-1111-1111', '台北市信義區松壽路1號', DATE_SUB(NOW(), INTERVAL 8 DAY), DATE_SUB(NOW(), INTERVAL 7 DAY))
ON DUPLICATE KEY UPDATE
  `total_amount` = VALUES(`total_amount`),
  `discount_amount` = VALUES(`discount_amount`),
  `shipping_fee` = VALUES(`shipping_fee`),
  `status` = VALUES(`status`),
  `payment_method` = VALUES(`payment_method`),
  `updated_at` = VALUES(`updated_at`);


-- 商品規格資料
INSERT INTO `product_variants`
(`product_id`, `variant_name`, `color`, `size`, `sku`, `stock`, `price_offset`, `is_active`, `created_at`) VALUES
(1, '標準版', 'default', 'F', 'SKU-001-STD', 50, 0, 1, NOW()),
(2, '標準版', 'default', 'F', 'SKU-002-STD', 45, 0, 1, NOW()),
(3, '標準版', 'default', 'F', 'SKU-003-STD', 5, 0, 1, NOW())
ON DUPLICATE KEY UPDATE
  `stock` = VALUES(`stock`),
  `price_offset` = VALUES(`price_offset`),
  `is_active` = VALUES(`is_active`);


-- 庫存預警資料
INSERT INTO `inventory_alerts`
(`product_id`, `variant_id`, `alert_type`, `threshold`, `current_stock`, `is_resolved`, `created_at`, `resolved_at`) VALUES
(3, NULL, 'low_stock', 20, 5, 0, DATE_SUB(NOW(), INTERVAL 1 DAY), NULL);


-- 支付交易資料
INSERT INTO `payment_transactions`
(`order_id`, `payment_method`, `amount`, `status`, `gateway_id`, `created_at`, `completed_at`) VALUES
(1, 'credit_card', 1210.00, 'success', 'TXN001', DATE_SUB(NOW(), INTERVAL 20 DAY), DATE_SUB(NOW(), INTERVAL 20 DAY)),
(2, 'credit_card', 609.00, 'success', 'TXN002', DATE_SUB(NOW(), INTERVAL 15 DAY), DATE_SUB(NOW(), INTERVAL 15 DAY)),
(3, 'paypal', 909.00, 'success', 'TXN003', DATE_SUB(NOW(), INTERVAL 12 DAY), DATE_SUB(NOW(), INTERVAL 12 DAY)),
(4, 'credit_card', 499.00, 'pending', 'TXN004', DATE_SUB(NOW(), INTERVAL 3 DAY), NULL),
(5, 'credit_card', 809.00, 'pending', 'TXN005', DATE_SUB(NOW(), INTERVAL 2 DAY), NULL),
(6, 'credit_card', 1530.00, 'success', 'TXN006', DATE_SUB(NOW(), INTERVAL 8 DAY), DATE_SUB(NOW(), INTERVAL 8 DAY))
ON DUPLICATE KEY UPDATE
  `amount` = VALUES(`amount`),
  `status` = VALUES(`status`),
  `completed_at` = VALUES(`completed_at`);


-- 物流訂單資料
INSERT INTO `logistics_orders`
(`order_id`, `logistics_provider`, `tracking_number`, `status`, `logistics_fee`, `estimated_delivery`, `created_at`, `updated_at`) VALUES
(1, 'SF Express', 'SF1001001001', 'delivered', 10.00, DATE_SUB(CURDATE(), INTERVAL 16 DAY), DATE_SUB(NOW(), INTERVAL 18 DAY), DATE_SUB(NOW(), INTERVAL 16 DAY)),
(2, 'SF Express', 'SF1001001002', 'delivered', 10.00, DATE_SUB(CURDATE(), INTERVAL 11 DAY), DATE_SUB(NOW(), INTERVAL 13 DAY), DATE_SUB(NOW(), INTERVAL 11 DAY)),
(3, 'ZTO Express', 'ZTO2001001003', 'shipped', 10.00, DATE_ADD(CURDATE(), INTERVAL 2 DAY), DATE_SUB(NOW(), INTERVAL 10 DAY), NOW()),
(6, 'SF Express', 'SF1001001006', 'delivered', 20.00, DATE_SUB(CURDATE(), INTERVAL 3 DAY), DATE_SUB(NOW(), INTERVAL 5 DAY), DATE_SUB(NOW(), INTERVAL 3 DAY))
ON DUPLICATE KEY UPDATE
  `status` = VALUES(`status`),
  `logistics_fee` = VALUES(`logistics_fee`),
  `estimated_delivery` = VALUES(`estimated_delivery`),
  `updated_at` = VALUES(`updated_at`);


-- 轉化事件資料（測試 + 展示）
INSERT INTO `analytics_events` (`session_id`, `event_type`, `product_id`, `created_at`) VALUES
('session_001', 'page_view', NULL, DATE_SUB(NOW(), INTERVAL 3 DAY)),
('session_002', 'page_view', NULL, DATE_SUB(NOW(), INTERVAL 3 DAY)),
('session_003', 'page_view', NULL, DATE_SUB(NOW(), INTERVAL 3 DAY)),
('session_004', 'page_view', NULL, DATE_SUB(NOW(), INTERVAL 3 DAY)),
('session_005', 'page_view', NULL, DATE_SUB(NOW(), INTERVAL 3 DAY)),
('session_001', 'product_view', 1, DATE_SUB(NOW(), INTERVAL 3 DAY)),
('session_002', 'product_view', 2, DATE_SUB(NOW(), INTERVAL 3 DAY)),
('session_003', 'product_view', 1, DATE_SUB(NOW(), INTERVAL 3 DAY)),
('session_004', 'product_view', 3, DATE_SUB(NOW(), INTERVAL 3 DAY)),
('session_001', 'add_to_cart', 1, DATE_SUB(NOW(), INTERVAL 3 DAY)),
('session_002', 'add_to_cart', 2, DATE_SUB(NOW(), INTERVAL 3 DAY)),
('session_004', 'add_to_cart', 3, DATE_SUB(NOW(), INTERVAL 3 DAY)),
('session_001', 'checkout_start', NULL, DATE_SUB(NOW(), INTERVAL 3 DAY)),
('session_002', 'checkout_start', NULL, DATE_SUB(NOW(), INTERVAL 3 DAY)),
('session_001', 'purchase', NULL, DATE_SUB(NOW(), INTERVAL 3 DAY)),
('session_002', 'purchase', NULL, DATE_SUB(NOW(), INTERVAL 3 DAY)),
('demo_page_001', 'page_view', NULL, DATE_SUB(NOW(), INTERVAL 3 DAY)),
('demo_page_002', 'page_view', NULL, DATE_SUB(NOW(), INTERVAL 3 DAY)),
('demo_page_003', 'page_view', NULL, DATE_SUB(NOW(), INTERVAL 3 DAY)),
('demo_page_004', 'page_view', NULL, DATE_SUB(NOW(), INTERVAL 3 DAY)),
('demo_page_005', 'page_view', NULL, DATE_SUB(NOW(), INTERVAL 3 DAY)),
('demo_prod_001', 'product_view', 1, DATE_SUB(NOW(), INTERVAL 3 DAY)),
('demo_prod_002', 'product_view', 2, DATE_SUB(NOW(), INTERVAL 3 DAY)),
('demo_prod_003', 'product_view', 1, DATE_SUB(NOW(), INTERVAL 3 DAY)),
('demo_prod_004', 'product_view', 3, DATE_SUB(NOW(), INTERVAL 3 DAY)),
('demo_cart_001', 'add_to_cart', 1, DATE_SUB(NOW(), INTERVAL 3 DAY)),
('demo_cart_002', 'add_to_cart', 2, DATE_SUB(NOW(), INTERVAL 3 DAY)),
('demo_cart_003', 'add_to_cart', 3, DATE_SUB(NOW(), INTERVAL 3 DAY)),
('demo_check_001', 'checkout_start', NULL, DATE_SUB(NOW(), INTERVAL 3 DAY)),
('demo_check_002', 'checkout_start', NULL, DATE_SUB(NOW(), INTERVAL 3 DAY)),
('demo_purch_001', 'purchase', NULL, DATE_SUB(NOW(), INTERVAL 3 DAY)),
('demo_purch_002', 'purchase', NULL, DATE_SUB(NOW(), INTERVAL 3 DAY));


-- 棄單資料（測試 + 展示）
INSERT INTO `abandoned_carts`
(`member_id`, `session_id`, `cart_items`, `cart_total`, `recovery_attempts`, `last_attempt_at`, `is_recovered`, `created_at`, `abandoned_at`) VALUES
(4, 'cart_session_001', '{"items": [{"product_id": 1, "quantity": 2}]}', 1000.00, 0, NULL, 0, DATE_SUB(NOW(), INTERVAL 2 DAY), DATE_SUB(NOW(), INTERVAL 2 DAY)),
(5, 'cart_session_002', '{"items": [{"product_id": 2, "quantity": 1}]}', 599.00, 0, NULL, 0, DATE_SUB(NOW(), INTERVAL 1 DAY), DATE_SUB(NOW(), INTERVAL 1 DAY)),
(4, 'cart_demo_001', '[{"product_id": 1, "quantity": 2}]', 1000.00, 0, NULL, 0, DATE_SUB(NOW(), INTERVAL 2 DAY), DATE_SUB(NOW(), INTERVAL 2 DAY)),
(5, 'cart_demo_002', '[{"product_id": 2, "quantity": 1}]', 599.00, 0, NULL, 0, DATE_SUB(NOW(), INTERVAL 1 DAY), DATE_SUB(NOW(), INTERVAL 1 DAY));


-- 資料統計
SELECT '測試 / 展示資料插入完成' AS `Status`;
SELECT 'Members' AS `Table_Name`, COUNT(*) AS `Count` FROM `members`
UNION ALL SELECT 'Orders', COUNT(*) FROM `orders`
UNION ALL SELECT 'Promotions', COUNT(*) FROM `promotions`
UNION ALL SELECT 'Promo Codes', COUNT(*) FROM `promo_codes`
UNION ALL SELECT 'Logistics Orders', COUNT(*) FROM `logistics_orders`
UNION ALL SELECT 'Analytics Events', COUNT(*) FROM `analytics_events`
UNION ALL SELECT 'Abandoned Carts', COUNT(*) FROM `abandoned_carts`;

COMMIT;
