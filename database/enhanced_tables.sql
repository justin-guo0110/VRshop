-- 增强的电商后台管理系统表

-- 1. 产品规格表（支持颜色、尺寸等组合）
CREATE TABLE IF NOT EXISTS `product_variants` (
  `variant_id` int NOT NULL AUTO_INCREMENT,
  `product_id` int NOT NULL,
  `variant_name` varchar(255) NOT NULL COMMENT '如：红色-M码',
  `sku` varchar(100) UNIQUE,
  `color` varchar(50),
  `size` varchar(50),
  `stock` int NOT NULL DEFAULT 0,
  `price_offset` decimal(10,2) DEFAULT 0.00 COMMENT '相对基础价格的偏移',
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`variant_id`),
  KEY `idx_product_id` (`product_id`),
  CONSTRAINT `fk_variant_product` FOREIGN KEY (`product_id`) REFERENCES `products`(`product_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. 库存预警表
CREATE TABLE IF NOT EXISTS `inventory_alerts` (
  `alert_id` int NOT NULL AUTO_INCREMENT,
  `product_id` int NOT NULL,
  `variant_id` int,
  `alert_type` enum('low_stock','zero_stock','overstock') NOT NULL,
  `threshold` int DEFAULT 20 COMMENT '触发预警阈值',
  `current_stock` int,
  `is_resolved` tinyint(1) DEFAULT 0,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `resolved_at` datetime,
  PRIMARY KEY (`alert_id`),
  KEY `idx_product_id` (`product_id`),
  CONSTRAINT `fk_alert_product` FOREIGN KEY (`product_id`) REFERENCES `products`(`product_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. 客户标签管理
CREATE TABLE IF NOT EXISTS `customer_labels` (
  `label_id` int NOT NULL AUTO_INCREMENT,
  `label_name` varchar(100) NOT NULL COMMENT 'VIP、高单价、流失客户等',
  `description` text,
  `color_code` varchar(7),
  `is_system` tinyint(1) DEFAULT 0 COMMENT '系统自动生成的标签',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`label_id`),
  UNIQUE KEY `label_name` (`label_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. 客户标签映射
CREATE TABLE IF NOT EXISTS `customer_label_mapping` (
  `mapping_id` int NOT NULL AUTO_INCREMENT,
  `member_id` int NOT NULL,
  `label_id` int NOT NULL,
  `assigned_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`mapping_id`),
  UNIQUE KEY `unique_member_label` (`member_id`, `label_id`),
  CONSTRAINT `fk_mapping_member` FOREIGN KEY (`member_id`) REFERENCES `members`(`member_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_mapping_label` FOREIGN KEY (`label_id`) REFERENCES `customer_labels`(`label_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. 促销活动表
CREATE TABLE IF NOT EXISTS `promotions` (
  `promotion_id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `description` text,
  `promotion_type` enum('global_discount','threshold_discount','add_on_purchase','bundle','free_shipping','coupon') NOT NULL,
  `discount_type` enum('percent','fixed') DEFAULT 'percent' COMMENT '百分比或固定金额',
  `discount_value` decimal(10,2) NOT NULL,
  `min_amount` decimal(10,2) DEFAULT 0 COMMENT '满减起点',
  `max_discount` decimal(10,2) COMMENT '最多优惠多少',
  `product_id` int COMMENT '针对特定产品或为NULL表示全体',
  `start_date` datetime NOT NULL,
  `end_date` datetime NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_by` int,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`promotion_id`),
  KEY `idx_dates` (`start_date`, `end_date`),
  CONSTRAINT `fk_promotion_creator` FOREIGN KEY (`created_by`) REFERENCES `members`(`member_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. 优惠券代码表
CREATE TABLE IF NOT EXISTS `promo_codes` (
  `code_id` int NOT NULL AUTO_INCREMENT,
  `promotion_id` int NOT NULL,
  `code` varchar(50) UNIQUE NOT NULL,
  `usage_limit` int DEFAULT NULL COMMENT '限制使用次数，NULL为无限',
  `usage_count` int DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`code_id`),
  CONSTRAINT `fk_code_promotion` FOREIGN KEY (`promotion_id`) REFERENCES `promotions`(`promotion_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. 弃单记录表
CREATE TABLE IF NOT EXISTS `abandoned_carts` (
  `cart_id` int NOT NULL AUTO_INCREMENT,
  `member_id` int NOT NULL,
  `session_id` varchar(100),
  `cart_items` json COMMENT '存储购物车项目',
  `cart_total` decimal(10,2),
  `recovery_attempts` int DEFAULT 0,
  `last_attempt_at` datetime,
  `is_recovered` tinyint(1) DEFAULT 0,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `abandoned_at` datetime,
  PRIMARY KEY (`cart_id`),
  KEY `idx_member_id` (`member_id`),
  CONSTRAINT `fk_cart_member` FOREIGN KEY (`member_id`) REFERENCES `members`(`member_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. 分析事件表（用于追踪和漏斗分析）
CREATE TABLE IF NOT EXISTS `analytics_events` (
  `event_id` int NOT NULL AUTO_INCREMENT,
  `member_id` int,
  `session_id` varchar(100),
  `event_type` enum('page_view','product_view','add_to_cart','checkout_start','checkout_complete','purchase') NOT NULL,
  `product_id` int,
  `property_json` json COMMENT '事件属性如：来源渠道、UTM参数等',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`event_id`),
  KEY `idx_member_id` (`member_id`),
  KEY `idx_event_type` (`event_type`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `fk_event_member` FOREIGN KEY (`member_id`) REFERENCES `members`(`member_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. 物流单号表
CREATE TABLE IF NOT EXISTS `logistics_orders` (
  `logistics_id` int NOT NULL AUTO_INCREMENT,
  `order_id` int NOT NULL,
  `logistics_provider` varchar(50) NOT NULL COMMENT '超商取货、黑猫等',
  `tracking_number` varchar(100),
  `status` enum('pending','created','shipped','delivered','cancelled') DEFAULT 'pending',
  `logistics_fee` decimal(10,2),
  `estimated_delivery` date,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`logistics_id`),
  UNIQUE KEY `tracking_number` (`tracking_number`),
  KEY `idx_order_id` (`order_id`),
  CONSTRAINT `fk_logistics_order` FOREIGN KEY (`order_id`) REFERENCES `orders`(`order_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. 支付交易表
CREATE TABLE IF NOT EXISTS `payment_transactions` (
  `transaction_id` int NOT NULL AUTO_INCREMENT,
  `order_id` int NOT NULL,
  `payment_method` varchar(50) NOT NULL COMMENT 'credit_card, cod等',
  `amount` decimal(10,2) NOT NULL,
  `status` enum('pending','success','failed','refunded') DEFAULT 'pending',
  `gateway_id` varchar(100) COMMENT '支付网关交易ID',
  `reconciliation_status` enum('not_reconciled','reconciled','mismatch') DEFAULT 'not_reconciled',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `completed_at` datetime,
  PRIMARY KEY (`transaction_id`),
  UNIQUE KEY `gateway_id` (`gateway_id`),
  KEY `idx_order_id` (`order_id`),
  CONSTRAINT `fk_payment_order` FOREIGN KEY (`order_id`) REFERENCES `orders`(`order_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 11. 订单额外字段（关联促销、物流、支付等）
ALTER TABLE `orders` ADD COLUMN IF NOT EXISTS `promotion_id` int;
ALTER TABLE `orders` ADD COLUMN IF NOT EXISTS `promo_code` varchar(50);
ALTER TABLE `orders` ADD COLUMN IF NOT EXISTS `discount_amount` decimal(10,2) DEFAULT 0;
ALTER TABLE `orders` ADD COLUMN IF NOT EXISTS `shipping_method` varchar(50);
ALTER TABLE `orders` ADD COLUMN IF NOT EXISTS `payment_method` varchar(50);
ALTER TABLE `orders` ADD COLUMN IF NOT EXISTS `source_channel` varchar(50) COMMENT 'facebook, google, direct等';
ALTER TABLE `orders` ADD COLUMN IF NOT EXISTS `utm_source` varchar(100);

-- 12. 销售数据缓存表（用于快速生成看板）
CREATE TABLE IF NOT EXISTS `sales_dashboard_cache` (
  `cache_id` int NOT NULL AUTO_INCREMENT,
  `metric_type` varchar(50) NOT NULL COMMENT 'daily_revenue, total_orders等',
  `date` date,
  `metric_value` decimal(15,2),
  `detail_json` json,
  `cached_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`cache_id`),
  UNIQUE KEY `unique_metric` (`metric_type`, `date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 创建初始标签
INSERT IGNORE INTO `customer_labels` (`label_id`, `label_name`, `description`, `color_code`, `is_system`)
VALUES 
(1, 'VIP', 'VIP 高级客户', '#FFD700', 1),
(2, '高单价', '单笔订单金额高', '#FF6B6B', 1),
(3, '流失客户', '很久未购买', '#808080', 1),
(4, '新客户', '首次购买', '#4CAF50', 1),
(5, '常购客户', '频繁购买', '#2196F3', 1);

COMMIT;
