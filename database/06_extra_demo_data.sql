SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+08:00";

USE vr_mall;

-- 1) 追加測試會員
INSERT IGNORE INTO members (email, password_hash, name, phone, role, created_at) VALUES
('demo.user1@vrshop.com', SHA2('123456', 256), '陳小明', '0911000001', 'member', DATE_SUB(NOW(), INTERVAL 30 DAY)),
('demo.user2@vrshop.com', SHA2('123456', 256), '林小美', '0911000002', 'member', DATE_SUB(NOW(), INTERVAL 24 DAY)),
('demo.user3@vrshop.com', SHA2('123456', 256), '黃小華', '0911000003', 'member', DATE_SUB(NOW(), INTERVAL 18 DAY)),
('demo.user4@vrshop.com', SHA2('123456', 256), '吳大同', '0911000004', 'member', DATE_SUB(NOW(), INTERVAL 10 DAY)),
('demo.user5@vrshop.com', SHA2('123456', 256), '蔡安妮', '0911000005', 'member', DATE_SUB(NOW(), INTERVAL 6 DAY));

-- 2) 追加會員地址
INSERT IGNORE INTO member_addresses (member_id, recipient_name, phone, address_line, is_default)
SELECT m.member_id, m.name, m.phone,
       CASE m.email
           WHEN 'demo.user1@vrshop.com' THEN '台北市中山區南京東路二段100號'
           WHEN 'demo.user2@vrshop.com' THEN '新北市板橋區文化路一段88號'
           WHEN 'demo.user3@vrshop.com' THEN '桃園市中壢區中正路66號'
           WHEN 'demo.user4@vrshop.com' THEN '台中市西屯區市政北一路120號'
           WHEN 'demo.user5@vrshop.com' THEN '高雄市前鎮區復興三路50號'
           ELSE '台灣'
       END,
       1
FROM members m
WHERE m.email IN (
    'demo.user1@vrshop.com',
    'demo.user2@vrshop.com',
    'demo.user3@vrshop.com',
    'demo.user4@vrshop.com',
    'demo.user5@vrshop.com'
)
AND NOT EXISTS (
    SELECT 1
    FROM member_addresses a
    WHERE a.member_id = m.member_id AND a.is_default = 1
);

-- 3) 追加近 14 天訂單（供後台銷售趨勢 / 今日營收 / 訂單列表展示）
INSERT INTO orders (
    member_id,
    address_id,
    ship_name,
    ship_phone,
    ship_address_line,
    total_amount,
    discount_amount,
    shipping_fee,
    shipping_method,
    payment_method,
    status,
    created_at,
    updated_at
)
SELECT
    m.member_id,
    (
        SELECT a.address_id
        FROM member_addresses a
        WHERE a.member_id = m.member_id
        ORDER BY a.is_default DESC, a.address_id ASC
        LIMIT 1
    ) AS address_id,
    m.name,
    m.phone,
    (
        SELECT a.address_line
        FROM member_addresses a
        WHERE a.member_id = m.member_id
        ORDER BY a.is_default DESC, a.address_id ASC
        LIMIT 1
    ) AS ship_address_line,
    CASE seq.n
        WHEN 1 THEN 325
        WHEN 2 THEN 489
        WHEN 3 THEN 799
        WHEN 4 THEN 650
        WHEN 5 THEN 905
        WHEN 6 THEN 420
        WHEN 7 THEN 1120
        WHEN 8 THEN 560
        WHEN 9 THEN 1399
        WHEN 10 THEN 745
        WHEN 11 THEN 880
        WHEN 12 THEN 520
        WHEN 13 THEN 670
        ELSE 458
    END AS total_amount,
    CASE
        WHEN seq.n IN (3, 7, 9, 11) THEN 80
        WHEN seq.n IN (5, 10, 13) THEN 40
        ELSE 0
    END AS discount_amount,
    CASE
        WHEN seq.n IN (3, 5, 7, 9, 11, 13) THEN 0
        ELSE 60
    END AS shipping_fee,
    CASE
        WHEN MOD(seq.n, 2) = 0 THEN '超商取貨'
        ELSE '宅配'
    END AS shipping_method,
    CASE
        WHEN seq.n IN (4, 8, 12) THEN '貨到付款'
        ELSE 'credit_card'
    END AS payment_method,
    CASE
        WHEN seq.n >= 13 THEN 'preparing'
        WHEN seq.n IN (10, 11, 12) THEN 'shipping'
        ELSE 'done'
    END AS status,
    DATE_SUB(NOW(), INTERVAL (15 - seq.n) DAY) AS created_at,
    DATE_SUB(NOW(), INTERVAL (15 - seq.n) DAY) AS updated_at
FROM (
    SELECT 1 AS n UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL
    SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL
    SELECT 9 UNION ALL SELECT 10 UNION ALL SELECT 11 UNION ALL SELECT 12 UNION ALL
    SELECT 13 UNION ALL SELECT 14
) seq
JOIN members m ON m.email = CASE MOD(seq.n, 5)
    WHEN 1 THEN 'demo.user1@vrshop.com'
    WHEN 2 THEN 'demo.user2@vrshop.com'
    WHEN 3 THEN 'demo.user3@vrshop.com'
    WHEN 4 THEN 'demo.user4@vrshop.com'
    ELSE 'demo.user5@vrshop.com'
END
WHERE NOT EXISTS (
    SELECT 1
    FROM orders o
    WHERE o.member_id = m.member_id
      AND DATE(o.created_at) = DATE(DATE_SUB(NOW(), INTERVAL (15 - seq.n) DAY))
      AND o.total_amount = CASE seq.n
            WHEN 1 THEN 325
            WHEN 2 THEN 489
            WHEN 3 THEN 799
            WHEN 4 THEN 650
            WHEN 5 THEN 905
            WHEN 6 THEN 420
            WHEN 7 THEN 1120
            WHEN 8 THEN 560
            WHEN 9 THEN 1399
            WHEN 10 THEN 745
            WHEN 11 THEN 880
            WHEN 12 THEN 520
            WHEN 13 THEN 670
            ELSE 458
      END
);

-- 4) 針對新插入的訂單補上 order_items（每筆 2~3 件商品）
INSERT INTO order_items (order_id, product_id, quantity, unit_price)
SELECT o.order_id, p1.product_id, 2, p1.price
FROM orders o
JOIN members m ON m.member_id = o.member_id
JOIN products p1 ON p1.product_id = (
    SELECT p.product_id FROM products p WHERE p.is_active = 1 ORDER BY p.product_id ASC LIMIT 1
)
WHERE m.email IN (
    'demo.user1@vrshop.com',
    'demo.user2@vrshop.com',
    'demo.user3@vrshop.com',
    'demo.user4@vrshop.com',
    'demo.user5@vrshop.com'
)
AND DATE(o.created_at) >= DATE_SUB(CURDATE(), INTERVAL 14 DAY)
AND NOT EXISTS (
    SELECT 1 FROM order_items oi WHERE oi.order_id = o.order_id
);

INSERT INTO order_items (order_id, product_id, quantity, unit_price)
SELECT o.order_id, p2.product_id, 1, p2.price
FROM orders o
JOIN members m ON m.member_id = o.member_id
JOIN products p2 ON p2.product_id = (
    SELECT p.product_id FROM products p WHERE p.is_active = 1 ORDER BY p.product_id ASC LIMIT 1,1
)
WHERE m.email IN (
    'demo.user1@vrshop.com',
    'demo.user2@vrshop.com',
    'demo.user3@vrshop.com',
    'demo.user4@vrshop.com',
    'demo.user5@vrshop.com'
)
AND DATE(o.created_at) >= DATE_SUB(CURDATE(), INTERVAL 14 DAY)
AND EXISTS (
    SELECT 1 FROM order_items oi WHERE oi.order_id = o.order_id
)
AND NOT EXISTS (
    SELECT 1 FROM order_items oi WHERE oi.order_id = o.order_id AND oi.product_id = p2.product_id
);

-- 5) 補一些分析事件數據（購物行為漏斗）
INSERT INTO analytics_events (member_id, session_id, event_type, product_id, property_json, created_at)
SELECT
    m.member_id,
    CONCAT('demo_session_', m.member_id, '_', d.n) AS session_id,
    CASE d.n
        WHEN 1 THEN 'page_view'
        WHEN 2 THEN 'product_view'
        WHEN 3 THEN 'add_to_cart'
        WHEN 4 THEN 'checkout_start'
        ELSE 'purchase'
    END AS event_type,
    CASE WHEN d.n IN (2, 3) THEN (
        SELECT p.product_id FROM products p WHERE p.is_active = 1 ORDER BY p.product_id ASC LIMIT 1
    ) ELSE NULL END AS product_id,
    JSON_OBJECT('source', 'seed_script', 'campaign', 'spring_demo') AS property_json,
    DATE_SUB(NOW(), INTERVAL (m.member_id % 7) DAY) AS created_at
FROM members m
JOIN (
    SELECT 1 AS n UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5
) d
WHERE m.email IN (
    'demo.user1@vrshop.com',
    'demo.user2@vrshop.com',
    'demo.user3@vrshop.com',
    'demo.user4@vrshop.com',
    'demo.user5@vrshop.com'
)
AND NOT EXISTS (
    SELECT 1
    FROM analytics_events e
    WHERE e.session_id = CONCAT('demo_session_', m.member_id, '_', d.n)
      AND e.event_type = CASE d.n
            WHEN 1 THEN 'page_view'
            WHEN 2 THEN 'product_view'
            WHEN 3 THEN 'add_to_cart'
            WHEN 4 THEN 'checkout_start'
            ELSE 'purchase'
      END
);

-- 6) 執行後檢查
SELECT 'Extra demo data imported' AS status;
SELECT 'members' AS table_name, COUNT(*) AS total_count FROM members
UNION ALL
SELECT 'orders', COUNT(*) FROM orders
UNION ALL
SELECT 'order_items', COUNT(*) FROM order_items
UNION ALL
SELECT 'analytics_events', COUNT(*) FROM analytics_events;

COMMIT;
