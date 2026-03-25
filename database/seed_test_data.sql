-- ================================================================
-- VR Mall 测试数据 - 完整版
-- ================================================================

-- ================================================================
-- 1. 创建管理员和测试客户
-- ================================================================

INSERT INTO members (email, password_hash, name, phone, role, created_at) VALUES
('admin@vrshop.com', '$2y$10$YIjlrBtos/Yh7gJ8p8Jdve9h8k7Q4R9s8M9o0P', '管理员', '139-0000-0001', 'admin', NOW()),
('vip@test.com', '$2y$10$N9qo8uLOickgx2ZMRZoMye', '张三', '138-1111-1111', 'member', DATE_SUB(NOW(), INTERVAL 60 DAY)),
('highvalue@test.com', '$2y$10$N9qo8uLOickgx2ZMRZoMye', '李四', '138-2222-2222', 'member', DATE_SUB(NOW(), INTERVAL 45 DAY)),
('new@test.com', '$2y$10$N9qo8uLOickgx2ZMRZoMye', '王五', '138-3333-3333', 'member', DATE_SUB(NOW(), INTERVAL 5 DAY)),
('regular@test.com', '$2y$10$N9qo8uLOickgx2ZMRZoMye', '赵六', '138-4444-4444', 'member', DATE_SUB(NOW(), INTERVAL 20 DAY)),
('churned@test.com', '$2y$10$N9qo8uLOickgx2ZMRZoMye', '孙七', '138-5555-5555', 'member', DATE_SUB(NOW(), INTERVAL 180 DAY));

-- ================================================================
-- 2. 为客户分配标签
-- ================================================================

INSERT INTO customer_label_mapping (member_id, label_id, assigned_at) VALUES
(2, 1, NOW()),
(3, 2, NOW()),
(4, 3, NOW()),
(5, 4, NOW()),
(6, 5, NOW());

-- ================================================================
-- 3. 创建促销活动
-- ================================================================

INSERT INTO promotions (title, description, promotion_type, discount_type, discount_value, min_order_amount, start_date, end_date, is_active, created_at) VALUES
('春节大促88折', '全场产品春节促销，享受88折优惠', 'global_discount', 'percent', 12, 0, DATE_SUB(NOW(), INTERVAL 5 DAY), DATE_ADD(NOW(), INTERVAL 10 DAY), 1, NOW()),
('满599立减99', '购物满599元立即减99元', 'min_amount_discount', 'fixed', 99, 599, NOW(), DATE_ADD(NOW(), INTERVAL 30 DAY), 1, NOW()),
('VIP专享88折', 'VIP会员专享88折优惠', 'vip_discount', 'percent', 12, 100, NOW(), DATE_ADD(NOW(), INTERVAL 90 DAY), 1, NOW());

-- ================================================================
-- 4. 生成优惠代码
-- ================================================================

INSERT INTO promo_codes (promotion_id, code, usage_count, usage_limit, is_active, created_at) VALUES
(1, 'SPRING2026', 2, 500, 1, NOW()),
(1, 'LUCKY88', 5, 300, 1, NOW()),
(2, 'SAVE99', 3, 1000, 1, NOW()),
(3, 'VIP88', 1, 100, 1, NOW());

-- ================================================================
-- 5. 创建订单记录
-- ================================================================

INSERT INTO orders (member_id, order_number, total_amount, shipping_fee, discount_amount, status, payment_method, created_at, updated_at) VALUES
(2, 'ORD20260320001', 1200, 10, 100, 'done', 'credit_card', DATE_SUB(NOW(), INTERVAL 20 DAY), DATE_SUB(NOW(), INTERVAL 19 DAY)),
(2, 'ORD20260321001', 599, 10, 0, 'done', 'credit_card', DATE_SUB(NOW(), INTERVAL 15 DAY), DATE_SUB(NOW(), INTERVAL 14 DAY)),
(3, 'ORD20260322001', 899, 10, 150, 'shipping', 'paypal', DATE_SUB(NOW(), INTERVAL 12 DAY), DATE_SUB(NOW(), INTERVAL 11 DAY)),
(4, 'ORD20260323001', 499, 0, 0, 'pending', 'credit_card', DATE_SUB(NOW(), INTERVAL 3 DAY), DATE_SUB(NOW(), INTERVAL 3 DAY)),
(5, 'ORD20260324001', 799, 10, 50, 'preparing', 'credit_card', DATE_SUB(NOW(), INTERVAL 2 DAY), DATE_SUB(NOW(), INTERVAL 2 DAY)),
(2, 'ORD20260325001', 1500, 20, 200, 'done', 'credit_card', DATE_SUB(NOW(), INTERVAL 8 DAY), DATE_SUB(NOW(), INTERVAL 7 DAY));

-- ================================================================
-- 6. 创建产品规格变体
-- ================================================================

INSERT INTO product_variants (product_id, variant_name, color, size, sku, stock, price_offset, is_active, created_at) VALUES
(1, '红色-M', 'red', 'M', 'SKU-001-R-M', 50, 0, 1, NOW()),
(1, '红色-L', 'red', 'L', 'SKU-001-R-L', 35, 50, 1, NOW()),
(1, '蓝色-M', 'blue', 'M', 'SKU-001-B-M', 40, 0, 1, NOW()),
(1, '蓝色-L', 'blue', 'L', 'SKU-001-B-L', 25, 50, 1, NOW()),
(2, '黑色-S', 'black', 'S', 'SKU-002-BK-S', 30, 0, 1, NOW()),
(2, '黑色-M', 'black', 'M', 'SKU-002-BK-M', 45, 0, 1, NOW()),
(3, '绿色-M', 'green', 'M', 'SKU-003-G-M', 5, 0, 1, NOW());

-- ================================================================
-- 7. 创建库存预警
-- ================================================================

INSERT INTO inventory_alerts (product_id, product_name, current_stock, alert_threshold, is_resolved, created_at, updated_at) VALUES
(3, 'VR Controller Set', 5, 20, 0, DATE_SUB(NOW(), INTERVAL 1 DAY), NOW());

-- ================================================================
-- 8. 创建支付交易记录
-- ================================================================

INSERT INTO payment_transactions (order_id, payment_method, amount, transaction_id, status, created_at) VALUES
(1, 'credit_card', 1210, 'TXN001', 'completed', DATE_SUB(NOW(), INTERVAL 20 DAY)),
(2, 'credit_card', 609, 'TXN002', 'completed', DATE_SUB(NOW(), INTERVAL 15 DAY)),
(3, 'paypal', 909, 'TXN003', 'completed', DATE_SUB(NOW(), INTERVAL 12 DAY)),
(4, 'credit_card', 499, 'TXN004', 'pending', DATE_SUB(NOW(), INTERVAL 3 DAY)),
(5, 'credit_card', 809, 'TXN005', 'pending', DATE_SUB(NOW(), INTERVAL 2 DAY)),
(6, 'credit_card', 1530, 'TXN006', 'completed', DATE_SUB(NOW(), INTERVAL 8 DAY));

-- ================================================================
-- 9. 创建物流订单
-- ================================================================

INSERT INTO logistics_orders (order_id, logistics_provider, tracking_number, recipient_name, recipient_phone, recipient_address, status, created_at, updated_at) VALUES
(1, 'SF Express', 'SF1001001001', '张三', '138-1111-1111', '北京市朝阳区建国路100号', 'delivered', DATE_SUB(NOW(), INTERVAL 18 DAY), DATE_SUB(NOW(), INTERVAL 16 DAY)),
(2, 'SF Express', 'SF1001001002', '张三', '138-1111-1111', '北京市朝阳区建国路100号', 'delivered', DATE_SUB(NOW(), INTERVAL 13 DAY), DATE_SUB(NOW(), INTERVAL 11 DAY)),
(3, 'ZTO Express', 'ZTO2001001003', '李四', '138-2222-2222', '上海市浦东新区世纪大道200号', 'in_transit', DATE_SUB(NOW(), INTERVAL 10 DAY), NOW()),
(6, 'SF Express', 'SF1001001006', '张三', '138-1111-1111', '北京市朝阳区建国路100号', 'delivered', DATE_SUB(NOW(), INTERVAL 5 DAY), DATE_SUB(NOW(), INTERVAL 3 DAY));

-- ================================================================
-- 10. 创建转化事件数据
-- ================================================================

INSERT INTO analytics_events (session_id, event_type, product_id, created_at) VALUES
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
('session_002', 'purchase', NULL, DATE_SUB(NOW(), INTERVAL 3 DAY));

-- ================================================================
-- 11. 创建棉单记录
-- ================================================================

INSERT INTO abandoned_carts (member_id, cart_data, cart_total, recovered, created_at, updated_at) VALUES
(4, '{"items": [{"product_id": 1, "quantity": 2}]}', 1000, 0, DATE_SUB(NOW(), INTERVAL 2 DAY), NOW()),
(5, '{"items": [{"product_id": 2, "quantity": 1}]}', 599, 0, DATE_SUB(NOW(), INTERVAL 1 DAY), NOW());

-- ================================================================
-- 数据统计
-- ================================================================

SELECT '测试数据插入完成' as Status;
SELECT 'Members' as Table_Name, COUNT(*) as Count FROM members
UNION ALL SELECT 'Orders', COUNT(*) FROM orders
UNION ALL SELECT 'Promotions', COUNT(*) FROM promotions
UNION ALL SELECT 'Promo Codes', COUNT(*) FROM promo_codes
UNION ALL SELECT 'Logistics', COUNT(*) FROM logistics_orders
UNION ALL SELECT 'Analytics Events', COUNT(*) FROM analytics_events
UNION ALL SELECT 'Abandoned Carts', COUNT(*) FROM abandoned_carts;
