-- 促銷配置表 - 用於後台管理促銷規則
CREATE TABLE IF NOT EXISTS `promotion_config` (
  `config_id` INT(11) NOT NULL AUTO_INCREMENT,
  `config_type` VARCHAR(50) NOT NULL,
  `config_key` VARCHAR(100) NOT NULL,
  `config_value` LONGTEXT NOT NULL,
  `description` VARCHAR(255) DEFAULT NULL,
  `is_active` TINYINT(1) DEFAULT 1,
  `updated_by` INT(11) DEFAULT NULL,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`config_id`),
  UNIQUE KEY `uk_config_type_key` (`config_type`, `config_key`),
  KEY `idx_config_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 初始免運設定
INSERT INTO `promotion_config` (`config_type`, `config_key`, `config_value`, `description`, `is_active`) VALUES
('shipping', 'home_fee', '100', '宅配基本運費', 1),
('shipping', 'convenience_fee', '60', '超商取貨基本運費', 1),
('shipping', 'home_threshold', '499', '宅配免運門檻', 1),
('shipping', 'convenience_threshold', '299', '超商取貨免運門檻', 1);

-- 初始組合優惠規則
INSERT INTO `promotion_config` (`config_type`, `config_key`, `config_value`, `description`, `is_active`) VALUES
('bundle', 'beverage_discount_qty', '2', '飲料組合- 最少購買數量', 1),
('bundle', 'beverage_discount_percent', '12', '飲料組合- 折扣百分比', 1),
('bundle', 'beverage_categories', 'json:[\"咖啡\",\"奶類\",\"巧克力\",\"果汁\",\"碳酸飲料\",\"茶類\",\"運動飲料\"]', '飲料組合- 包含分類', 1),
('bundle', 'snack_discount_qty', '3', '零食組合- 最少購買數量', 1),
('bundle', 'snack_discount_fixed', '20', '零食組合- 每組折扣金額', 1),
('bundle', 'snack_categories', 'json:[\"糖果\",\"膨化零食\",\"餅乾\"]', '零食組合- 包含分類', 1);
