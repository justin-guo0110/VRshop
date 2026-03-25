-- ================================================================
-- VR Mall 演示数据脚本
-- ================================================================

-- ================================================================
-- 1. 创建转化事件 (用于分析仪表板)
-- ================================================================

DELETE FROM analytics_events WHERE session_id LIKE 'demo_%';

INSERT INTO analytics_events (session_id, event_type, product_id, created_at) VALUES
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

-- ================================================================
-- 2. 创建棉单记录
-- ================================================================

DELETE FROM abandoned_carts WHERE member_id IN (4, 5, 6);

INSERT INTO abandoned_carts (member_id, session_id, cart_items, cart_total, abandoned_at) VALUES
(4, 'cart_demo_001', '[{"product_id": 1, "quantity": 2}]', 1000, DATE_SUB(NOW(), INTERVAL 2 DAY)),
(5, 'cart_demo_002', '[{"product_id": 2, "quantity": 1}]', 599, DATE_SUB(NOW(), INTERVAL 1 DAY));

-- ================================================================
-- 数据验证统计
-- ================================================================

SELECT '✅ 演示数据插入成功' as Status;
SELECT 'Analytics Events' as Table_Name, COUNT(*) FROM analytics_events WHERE session_id LIKE 'demo_%'
UNION ALL SELECT 'Abandoned Carts', COUNT(*) FROM abandoned_carts WHERE member_id IN (4,5,6);
