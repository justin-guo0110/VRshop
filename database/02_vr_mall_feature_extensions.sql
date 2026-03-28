SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+08:00";

USE vr_mall;


-- 商品規格資料表（支援顏色、尺寸等組合）
CREATE TABLE IF NOT EXISTS `product_variants` (
  `variant_id` int NOT NULL AUTO_INCREMENT,
  `product_id` int NOT NULL,
  `variant_name` varchar(255) NOT NULL COMMENT '如：紅色-M碼',
  `sku` varchar(100) UNIQUE,
  `color` varchar(50),
  `size` varchar(50),
  `stock` int NOT NULL DEFAULT 0,
  `price_offset` decimal(10,2) DEFAULT 0.00 COMMENT '相對基礎價格的偏移',
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`variant_id`),
  KEY `idx_product_id` (`product_id`),
  CONSTRAINT `fk_variant_product`
    FOREIGN KEY (`product_id`) REFERENCES `products`(`product_id`)
    ON DELETE CASCADE
) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;


-- 庫存預警資料表
CREATE TABLE IF NOT EXISTS `inventory_alerts` (
  `alert_id` int NOT NULL AUTO_INCREMENT,
  `product_id` int NOT NULL,
  `variant_id` int DEFAULT NULL,
  `alert_type` enum('low_stock','zero_stock','overstock') NOT NULL,
  `threshold` int DEFAULT 20 COMMENT '觸發預警閾值',
  `current_stock` int DEFAULT NULL,
  `is_resolved` tinyint(1) DEFAULT 0,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `resolved_at` datetime DEFAULT NULL,
  PRIMARY KEY (`alert_id`),
  KEY `idx_product_id` (`product_id`),
  CONSTRAINT `fk_alert_product`
    FOREIGN KEY (`product_id`) REFERENCES `products`(`product_id`)
    ON DELETE CASCADE
) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;


-- 客戶標籤資料表
CREATE TABLE IF NOT EXISTS `customer_labels` (
  `label_id` int NOT NULL AUTO_INCREMENT,
  `label_name` varchar(100) NOT NULL COMMENT 'VIP、高單價、流失客戶等',
  `description` text,
  `color_code` varchar(7),
  `is_system` tinyint(1) DEFAULT 0 COMMENT '系統自動生成的標籤',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`label_id`),
  UNIQUE KEY `label_name` (`label_name`)
) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;


-- 客戶標籤對應資料表
CREATE TABLE IF NOT EXISTS `customer_label_mapping` (
  `mapping_id` int NOT NULL AUTO_INCREMENT,
  `member_id` int NOT NULL,
  `label_id` int NOT NULL,
  `assigned_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`mapping_id`),
  UNIQUE KEY `unique_member_label` (`member_id`, `label_id`),
  CONSTRAINT `fk_mapping_member`
    FOREIGN KEY (`member_id`) REFERENCES `members`(`member_id`)
    ON DELETE CASCADE,
  CONSTRAINT `fk_mapping_label`
    FOREIGN KEY (`label_id`) REFERENCES `customer_labels`(`label_id`)
    ON DELETE CASCADE
) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;


-- 促銷活動資料表
CREATE TABLE IF NOT EXISTS `promotions` (
  `promotion_id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `description` text,
  `promotion_type` enum('global_discount','threshold_discount','add_on_purchase','bundle','free_shipping','coupon') NOT NULL,
  `discount_type` enum('percent','fixed') DEFAULT 'percent' COMMENT '百分比或固定金額',
  `discount_value` decimal(10,2) NOT NULL,
  `min_amount` decimal(10,2) DEFAULT 0 COMMENT '滿減起點',
  `max_discount` decimal(10,2) DEFAULT NULL COMMENT '最多優惠多少',
  `product_id` int DEFAULT NULL COMMENT '針對特定產品或為NULL表示全體',
  `start_date` datetime NOT NULL,
  `end_date` datetime NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_by` int DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`promotion_id`),
  KEY `idx_dates` (`start_date`, `end_date`),
  KEY `idx_product_id` (`product_id`),
  CONSTRAINT `fk_promotion_creator`
    FOREIGN KEY (`created_by`) REFERENCES `members`(`member_id`)
    ON DELETE SET NULL,
  CONSTRAINT `fk_promotion_product`
    FOREIGN KEY (`product_id`) REFERENCES `products`(`product_id`)
    ON DELETE SET NULL
) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;


-- 優惠碼資料表
CREATE TABLE IF NOT EXISTS `promo_codes` (
  `code_id` int NOT NULL AUTO_INCREMENT,
  `promotion_id` int NOT NULL,
  `code` varchar(50) UNIQUE NOT NULL,
  `usage_limit` int DEFAULT NULL COMMENT '限制使用次數，NULL為無限',
  `usage_count` int DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`code_id`),
  CONSTRAINT `fk_code_promotion`
    FOREIGN KEY (`promotion_id`) REFERENCES `promotions`(`promotion_id`)
    ON DELETE CASCADE
) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;


-- 棄單資料表
CREATE TABLE IF NOT EXISTS `abandoned_carts` (
  `cart_id` int NOT NULL AUTO_INCREMENT,
  `member_id` int NOT NULL,
  `session_id` varchar(100) DEFAULT NULL,
  `cart_items` json COMMENT '儲存購物車項目',
  `cart_total` decimal(10,2) DEFAULT NULL,
  `recovery_attempts` int DEFAULT 0,
  `last_attempt_at` datetime DEFAULT NULL,
  `is_recovered` tinyint(1) DEFAULT 0,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `abandoned_at` datetime DEFAULT NULL,
  PRIMARY KEY (`cart_id`),
  KEY `idx_member_id` (`member_id`),
  CONSTRAINT `fk_cart_member`
    FOREIGN KEY (`member_id`) REFERENCES `members`(`member_id`)
    ON DELETE CASCADE
) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;


-- 分析事件資料表
CREATE TABLE IF NOT EXISTS `analytics_events` (
  `event_id` int NOT NULL AUTO_INCREMENT,
  `member_id` int DEFAULT NULL,
  `session_id` varchar(100) DEFAULT NULL,
  `event_type` enum('page_view','product_view','add_to_cart','checkout_start','checkout_complete','purchase') NOT NULL,
  `product_id` int DEFAULT NULL,
  `property_json` json COMMENT '事件屬性如：來源渠道、UTM參數等',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`event_id`),
  KEY `idx_member_id` (`member_id`),
  KEY `idx_event_type` (`event_type`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_product_id` (`product_id`),
  CONSTRAINT `fk_event_member`
    FOREIGN KEY (`member_id`) REFERENCES `members`(`member_id`)
    ON DELETE SET NULL,
  CONSTRAINT `fk_event_product`
    FOREIGN KEY (`product_id`) REFERENCES `products`(`product_id`)
    ON DELETE SET NULL
) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;


-- 物流訂單資料表
CREATE TABLE IF NOT EXISTS `logistics_orders` (
  `logistics_id` int NOT NULL AUTO_INCREMENT,
  `order_id` int NOT NULL,
  `logistics_provider` varchar(50) NOT NULL COMMENT '超商取貨、黑貓等',
  `tracking_number` varchar(100) DEFAULT NULL,
  `status` enum('pending','created','shipped','delivered','cancelled') DEFAULT 'pending',
  `logistics_fee` decimal(10,2) DEFAULT NULL,
  `estimated_delivery` date DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`logistics_id`),
  UNIQUE KEY `tracking_number` (`tracking_number`),
  KEY `idx_order_id` (`order_id`),
  CONSTRAINT `fk_logistics_order`
    FOREIGN KEY (`order_id`) REFERENCES `orders`(`order_id`)
    ON DELETE CASCADE
) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;


-- 支付交易資料表
CREATE TABLE IF NOT EXISTS `payment_transactions` (
  `transaction_id` int NOT NULL AUTO_INCREMENT,
  `order_id` int NOT NULL,
  `payment_method` varchar(50) NOT NULL COMMENT 'credit_card, cod等',
  `amount` decimal(10,2) NOT NULL,
  `status` enum('pending','success','failed','refunded') DEFAULT 'pending',
  `gateway_id` varchar(100) DEFAULT NULL COMMENT '支付閘道交易ID',
  `reconciliation_status` enum('not_reconciled','reconciled','mismatch') DEFAULT 'not_reconciled',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `completed_at` datetime DEFAULT NULL,
  PRIMARY KEY (`transaction_id`),
  UNIQUE KEY `gateway_id` (`gateway_id`),
  KEY `idx_order_id` (`order_id`),
  CONSTRAINT `fk_payment_order`
    FOREIGN KEY (`order_id`) REFERENCES `orders`(`order_id`)
    ON DELETE CASCADE
) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;


-- 銷售快取資料表
CREATE TABLE IF NOT EXISTS `sales_dashboard_cache` (
  `cache_id` int NOT NULL AUTO_INCREMENT,
  `metric_type` varchar(50) NOT NULL COMMENT 'daily_revenue, total_orders等',
  `date` date DEFAULT NULL,
  `metric_value` decimal(15,2) DEFAULT NULL,
  `detail_json` json DEFAULT NULL,
  `cached_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`cache_id`),
  UNIQUE KEY `unique_metric` (`metric_type`, `date`)
) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;


-- 擴充訂單資料表欄位
ALTER TABLE `orders`
  ADD COLUMN IF NOT EXISTS `order_number` varchar(50) DEFAULT NULL AFTER `order_id`,
  ADD COLUMN IF NOT EXISTS `promotion_id` int DEFAULT NULL AFTER `address_id`,
  ADD COLUMN IF NOT EXISTS `promo_code` varchar(50) DEFAULT NULL AFTER `promotion_id`,
  ADD COLUMN IF NOT EXISTS `discount_amount` decimal(10,2) NOT NULL DEFAULT 0.00 AFTER `total_amount`,
  ADD COLUMN IF NOT EXISTS `shipping_fee` decimal(10,2) NOT NULL DEFAULT 0.00 AFTER `discount_amount`,
  ADD COLUMN IF NOT EXISTS `shipping_method` varchar(50) DEFAULT NULL AFTER `shipping_fee`,
  ADD COLUMN IF NOT EXISTS `payment_method` varchar(50) DEFAULT NULL AFTER `shipping_method`,
  ADD COLUMN IF NOT EXISTS `source_channel` varchar(50) DEFAULT NULL COMMENT 'facebook, google, direct等' AFTER `payment_method`,
  ADD COLUMN IF NOT EXISTS `utm_source` varchar(100) DEFAULT NULL AFTER `source_channel`,
  ADD COLUMN IF NOT EXISTS `updated_at` timestamp NULL DEFAULT NULL AFTER `created_at`;

ALTER TABLE `orders`
  ADD UNIQUE KEY IF NOT EXISTS `uk_order_number` (`order_number`);

ALTER TABLE `orders`
  ADD CONSTRAINT `fk_order_promotion`
    FOREIGN KEY (`promotion_id`) REFERENCES `promotions`(`promotion_id`)
    ON DELETE SET NULL;


-- 預設客戶標籤資料
INSERT IGNORE INTO `customer_labels` (`label_id`, `label_name`, `description`, `color_code`, `is_system`) VALUES
(1, 'VIP', 'VIP 高級客戶', '#FFD700', 1),
(2, '高單價', '單筆訂單金額高', '#FF6B6B', 1),
(3, '流失客戶', '很久未購買', '#808080', 1),
(4, '新客戶', '首次購買', '#4CAF50', 1),
(5, '常購客戶', '頻繁購買', '#2196F3', 1);

COMMIT;
