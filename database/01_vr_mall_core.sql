SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+08:00";

USE vr_mall;


-- 會員資料表
CREATE TABLE `members` (
  `member_id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `role` enum('member','admin') NOT NULL DEFAULT 'member',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`member_id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;

-- 預設會員資料
INSERT INTO `members` (`member_id`, `email`, `password_hash`, `name`, `phone`, `role`, `created_at`) VALUES
(1, '123@gmail.com', SHA2('123', 256), 'Tom', '0987655341', 'admin', '2025-11-24 06:51:29'),
(2, '456@gmail.com', SHA2('456', 256), 'John', '0965548121', 'member', '2025-11-24 06:51:53');


-- 聊天紀錄資料表
CREATE TABLE `chat_messages` (
  `message_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `session_id` varchar(255) DEFAULT NULL,
  `sender` enum('user','admin') NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`message_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `fk_chat_user`
    FOREIGN KEY (`user_id`) REFERENCES `members`(`member_id`)
    ON DELETE SET NULL
) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;


-- 會員地址資料表
CREATE TABLE `member_addresses` (
  `address_id` int(11) NOT NULL AUTO_INCREMENT,
  `member_id` int(11) NOT NULL,
  `recipient_name` varchar(255) NOT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `address_line` varchar(500) NOT NULL,
  `is_default` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`address_id`),
  KEY `member_id` (`member_id`),
  CONSTRAINT `fk_address_member`
    FOREIGN KEY (`member_id`) REFERENCES `members`(`member_id`)
    ON DELETE CASCADE
) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;

-- 預設地址資料
INSERT INTO `member_addresses` (`address_id`, `member_id`, `recipient_name`, `phone`, `address_line`, `is_default`) VALUES
(1, 2, 'John', '0965548121', '台中市北屯區文心路四段800號', 1);


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

-- 咖啡
('貝納頌 海鹽焦糖風味', '咖啡', '', 39.00, 100, '../image/咖啡/貝納頌 海鹽焦糖風味.jpg', 1),
('貝納頌 榛果風味拿鐵', '咖啡', '', 39.00, 100, '../image/咖啡/貝納頌 榛果風味拿鐵.jpg', 1),
('貝納頌 黑咖啡', '咖啡', '', 35.00, 100, '../image/咖啡/貝納頌 黑咖啡.jpg', 1),
('貝納頌 經典曼特寧風味', '咖啡', '', 39.00, 100, '../image/咖啡/貝納頌 經典曼特寧風味.jpg', 1),
('貝納頌 經典拿鐵', '咖啡', '', 39.00, 100, '../image/咖啡/貝納頌 經典拿鐵.jpg', 1),
('伯朗 EX雙倍濃烈咖啡', '咖啡', '', 35.00, 100, '../image/咖啡/伯朗 EX雙倍濃烈咖啡.jpg', 1),
('伯朗咖啡 原味', '咖啡', '', 35.00, 100, '../image/咖啡/伯朗咖啡 原味.jpg', 1),
('伯朗咖啡 藍山風味', '咖啡', '', 35.00, 100, '../image/咖啡/伯朗咖啡 藍山風味.jpg', 1),
('咖啡廣場 調合式冰咖啡', '咖啡', '', 30.00, 100, '../image/咖啡/咖啡廣場 調合式冰咖啡.jpg', 1),
('咖啡廣場 奶香特調咖啡', '咖啡', '', 30.00, 100, '../image/咖啡/咖啡廣場 奶香特調咖啡.jpg', 1),

-- 運動飲料
('Red Bull 紅牛能量飲料 六月莓口味', '運動飲料', '', 39.00, 100, '../image/運動飲料/Red Bull 紅牛能量飲料 六月莓口味.jpg', 1),
('Red Bull 紅牛能量飲料 仙人掌口味', '運動飲料', '', 39.00, 100, '../image/運動飲料/Red Bull 紅牛能量飲料 仙人掌口味.jpg', 1),
('Red Bull 紅牛能量飲料 西瓜口味', '運動飲料', '', 39.00, 100, '../image/運動飲料/Red Bull 紅牛能量飲料 西瓜口味.jpg', 1),
('Red Bull 紅牛能量飲料 草莓杏桃口味', '運動飲料', '', 39.00, 100, '../image/運動飲料/Red Bull 紅牛能量飲料 草莓杏桃口味.jpg', 1),
('Red Bull 紅牛能量飲料 椰子莓果口味', '運動飲料', '', 39.00, 100, '../image/運動飲料/Red Bull 紅牛能量飲料 椰子莓果口味.jpg', 1),
('Red Bull 紅牛能量飲料 熱帶水果口味', '運動飲料', '', 39.00, 100, '../image/運動飲料/Red Bull 紅牛能量飲料 熱帶水果口味.jpg', 1),
('Red Bull 紅牛能量飲料', '運動飲料', '', 39.00, 100, '../image/運動飲料/Red Bull 紅牛能量飲料.jpg', 1),
('Red Bull 無糖能量飲料', '運動飲料', '', 39.00, 100, '../image/運動飲料/Red Bull 無糖能量飲料.jpg', 1),
('舒跑 運動飲料 易開罐', '運動飲料', '', 25.00, 100, '../image/運動飲料/舒跑 運動飲料 易開罐.jpg', 1),
('舒跑 運動飲料 鋁箔包', '運動飲料', '', 25.00, 100, '../image/運動飲料/舒跑 運動飲料 鋁箔包.jpg', 1),
('舒跑 運動飲料', '運動飲料', '', 25.00, 100, '../image/運動飲料/舒跑 運動飲料.jpg', 1),
('黑松 FIN健康補給飲料 易開罐', '運動飲料', '', 25.00, 100, '../image/運動飲料/黑松 FIN健康補給飲料 易開罐.jpg', 1),
('黑松 FIN健康補給飲料 鋁箔包', '運動飲料', '', 25.00, 100, '../image/運動飲料/黑松 FIN健康補給飲料 鋁箔包.jpg', 1),
('黑松 FIN健康補給飲料', '運動飲料', '', 25.00, 100, '../image/運動飲料/黑松 FIN健康補給飲料.jpg', 1),
('寶礦力水得 低卡', '運動飲料', '', 25.00, 100, '../image/運動飲料/寶礦力水得 低卡.jpg', 1),
('寶礦力水得', '運動飲料', '', 25.00, 100, '../image/運動飲料/寶礦力水得.jpg', 1),

-- 碳酸飲料
('七喜汽水 易開罐', '碳酸飲料', '', 30.00, 100, '../image/碳酸飲料/七喜汽水 易開罐.jpg', 1),
('七喜汽水', '碳酸飲料', '', 30.00, 100, '../image/碳酸飲料/七喜汽水.jpg', 1),
('可口可樂', '碳酸飲料', '', 35.00, 100, '../image/碳酸飲料/可口可樂.jpg', 1),
('芬達葡萄汽水', '碳酸飲料', '', 30.00, 100, '../image/碳酸飲料/芬達葡萄汽水.jpg', 1),
('芬達橘子汽水', '碳酸飲料', '', 30.00, 100, '../image/碳酸飲料/芬達橘子汽水.jpg', 1),
('金車 奧利多碳酸飲料', '碳酸飲料', '', 25.00, 100, '../image/碳酸飲料/金車 奧利多碳酸飲料.jpg', 1),
('金車 微舒打 葡萄果汁汽水', '碳酸飲料', '', 25.00, 100, '../image/碳酸飲料/金車 微舒打 葡萄果汁汽水.jpg', 1),
('雪碧汽水', '碳酸飲料', '', 30.00, 100, '../image/碳酸飲料/雪碧汽水.jpg', 1),
('維大力汽水', '碳酸飲料', '', 25.00, 100, '../image/碳酸飲料/維大力汽水.jpg', 1),
('維他露P', '碳酸飲料', '', 25.00, 100, '../image/碳酸飲料/維他露P.jpg', 1),
('可口可樂 易開罐', '碳酸飲料', '', 35.00, 100, '../image/碳酸飲料/可口可樂 易開罐.jpg', 1),
('百事可樂', '碳酸飲料', '', 30.00, 100, '../image/碳酸飲料/百事可樂.jpg', 1),
('金車 麥根沙士', '碳酸飲料', '', 25.00, 100, '../image/碳酸飲料/金車 麥根沙士.jpg', 1),
('雪碧汽水 罐裝', '碳酸飲料', '', 30.00, 100, '../image/碳酸飲料/雪碧汽水 罐裝.jpg', 1),
('黑松沙士', '碳酸飲料', '', 25.00, 100, '../image/碳酸飲料/黑松沙士.jpg', 1),
('激浪汽水', '碳酸飲料', '', 30.00, 100, '../image/碳酸飲料/激浪汽水.jpg', 1),

-- 茶類
('古道 百香綠茶', '茶類', '', 25.00, 100, '../image/茶類/古道 百香綠茶.jpg', 1),
('古道 梅子綠茶', '茶類', '', 25.00, 100, '../image/茶類/古道 梅子綠茶.jpg', 1),
('紅茶花伝 皇家奶茶', '茶類', '', 30.00, 100, '../image/茶類/紅茶花伝 皇家奶茶.jpg', 1),
('紅茶花伝 皇家紅茶', '茶類', '', 30.00, 100, '../image/茶類/紅茶花伝 皇家紅茶.jpg', 1),
('純喫茶 紅茶', '茶類', '', 25.00, 100, '../image/茶類/純喫茶 紅茶.jpg', 1),
('純喫茶 綠茶', '茶類', '', 25.00, 100, '../image/茶類/純喫茶 綠茶.jpg', 1),
('純喫茶 鮮柚綠茶', '茶類', '', 25.00, 100, '../image/茶類/純喫茶 鮮柚綠茶.jpg', 1),
('純喫茶 檸檬紅茶', '茶類', '', 25.00, 100, '../image/茶類/純喫茶 檸檬紅茶.jpg', 1),
('純喫茶 檸檬綠茶', '茶類', '', 25.00, 100, '../image/茶類/純喫茶 檸檬綠茶.jpg', 1),
('茶裏王 日式綠茶', '茶類', '', 25.00, 100, '../image/茶類/茶裏王 日式綠茶.jpg', 1),
('茶裏王 台式綠茶', '茶類', '', 25.00, 100, '../image/茶類/茶裏王 台式綠茶.jpg', 1),
('茶裏王 白毫烏龍', '茶類', '', 25.00, 100, '../image/茶類/茶裏王 白毫烏龍.jpg', 1),
('茶裏王 英式紅茶', '茶類', '', 25.00, 100, '../image/茶類/茶裏王 英式紅茶.jpg', 1),
('御茶園 日式綠茶', '茶類', '', 25.00, 100, '../image/茶類/御茶園 日式綠茶.jpg', 1),
('御茶園 台灣四季春', '茶類', '', 25.00, 100, '../image/茶類/御茶園 台灣四季春.jpg', 1),
('御茶園 特上紅茶', '茶類', '', 25.00, 100, '../image/茶類/御茶園 特上紅茶.jpg', 1),
('御茶園 特上檸檬茶', '茶類', '', 25.00, 100, '../image/茶類/御茶園 特上檸檬茶.jpg', 1),
('統一 麥香 阿薩姆奶茶', '茶類', '', 20.00, 100, '../image/茶類/統一 麥香 阿薩姆奶茶.jpg', 1),
('統一 麥香 阿薩姆紅茶', '茶類', '', 20.00, 100, '../image/茶類/統一 麥香 阿薩姆紅茶.jpg', 1),
('統一 麥香 錫蘭奶茶', '茶類', '', 20.00, 100, '../image/茶類/統一 麥香 錫蘭奶茶.jpg', 1),
('統一 麥香 檸檬紅茶', '茶類', '', 20.00, 100, '../image/茶類/統一 麥香 檸檬紅茶.jpg', 1),
('統一 麥香 奶茶', '茶類', '', 20.00, 100, '../image/茶類/統一 麥香奶茶.jpg', 1),
('統一 麥香 紅茶', '茶類', '', 20.00, 100, '../image/茶類/統一 麥香紅茶.jpg', 1),
('統一 麥香 綠茶', '茶類', '', 20.00, 100, '../image/茶類/統一 麥香綠茶.jpg', 1),
('統一 飲冰室茶集 紅奶茶', '茶類', '', 25.00, 100, '../image/茶類/統一 飲冰室茶集 紅奶茶.jpg', 1),
('統一 飲冰室茶集 烏龍奶茶', '茶類', '', 25.00, 100, '../image/茶類/統一 飲冰室茶集 烏龍奶茶.jpg', 1),
('統一 飲冰室茶集 綠奶茶', '茶類', '', 25.00, 100, '../image/茶類/統一 飲冰室茶集 綠奶茶.jpg', 1),

-- 醬料
('牛頭牌 原味沙茶醬', '醬料', '', 65.00, 100, '../image/醬料/牛頭牌 原味沙茶醬.jpg', 1),
('牛頭牌 麻辣沙茶醬', '醬料', '', 65.00, 100, '../image/醬料/牛頭牌 麻辣沙茶醬.jpg', 1),
('四季 釀造醬油', '醬料', '', 89.00, 100, '../image/醬料/四季 釀造醬油.jpg', 1),
('味全 烤肉醬', '醬料', '', 49.00, 100, '../image/醬料/味全 烤肉醬.jpg', 1),
('味全 辣味烤肉醬', '醬料', '', 49.00, 100, '../image/醬料/味全 辣味烤肉醬.jpg', 1),
('愛之味 甜辣醬', '醬料', '', 45.00, 100, '../image/醬料/愛之味 甜辣醬.jpg', 1),
('萬家香 香菇素蠔油', '醬料', '', 79.00, 100, '../image/醬料/萬家香 香菇素蠔油.jpg', 1),
('維力 炸醬罐', '醬料', '', 55.00, 100, '../image/醬料/維力 炸醬罐.jpg', 1),
('維力 素食炸醬罐', '醬料', '', 55.00, 100, '../image/醬料/維力 素食炸醬罐.jpg', 1),

-- 果汁
('光泉 果汁時刻 柳橙綜合果汁', '果汁', '', 30.00, 100, '../image/果汁/光泉 果汁時刻 柳橙綜合果汁.jpg', 1),
('光泉 果汁時刻 葡萄綜合果汁', '果汁', '', 30.00, 100, '../image/果汁/光泉 果汁時刻 葡萄綜合果汁.jpg', 1),
('光泉 果汁時刻 蘋果綜合果汁', '果汁', '', 30.00, 100, '../image/果汁/光泉 果汁時刻 蘋果綜合果汁.jpg', 1),
('每日C 柳橙汁', '果汁', '', 35.00, 100, '../image/果汁/每日C 柳橙汁.jpg', 1),
('每日C 葡萄汁', '果汁', '', 35.00, 100, '../image/果汁/每日C 葡萄汁.jpg', 1),
('波蜜 一日蔬果 蔬果汁', '果汁', '', 30.00, 100, '../image/果汁/波蜜 一日蔬果 蔬果汁.jpg', 1),
('波蜜 一日蔬果 紫色蔬果汁', '果汁', '', 30.00, 100, '../image/果汁/波蜜 一日蔬果 紫色蔬果汁.jpg', 1),
('波蜜 一日蔬果 蘋果汁', '果汁', '', 30.00, 100, '../image/果汁/波蜜 一日蔬果 蘋果汁.jpg', 1),
('農搾 金桔檸檬飲', '果汁', '', 30.00, 100, '../image/果汁/農搾 金桔檸檬飲.jpg', 1),
('農搾 百香檸檬飲', '果汁', '', 30.00, 100, '../image/果汁/農搾 百香檸檬飲.jpg', 1),
('農搾 檸檬飲', '果汁', '', 30.00, 100, '../image/果汁/農搾 檸檬飲.jpg', 1),

-- 奶類
('光泉 巧克力牛乳', '奶類', '', 32.00, 100, '../image/奶類/光泉 巧克力牛乳.jpg', 1),
('光泉 低脂高鈣牛乳', '奶類', '', 32.00, 100, '../image/奶類/光泉 低脂高鈣牛乳.jpg', 1),
('光泉 果汁牛乳', '奶類', '', 32.00, 100, '../image/奶類/光泉 果汁牛乳.jpg', 1),
('光泉 珍穀堅果牛乳', '奶類', '', 32.00, 100, '../image/奶類/光泉 珍穀堅果牛乳.jpg', 1),
('光泉 草莓牛乳', '奶類', '', 32.00, 100, '../image/奶類/光泉 草莓牛乳.jpg', 1),
('光泉 高鈣牛乳', '奶類', '', 32.00, 100, '../image/奶類/光泉 高鈣牛乳.jpg', 1),
('光泉 麥芽牛乳', '奶類', '', 32.00, 100, '../image/奶類/光泉 麥芽牛乳.jpg', 1),
('光泉 黑芝麻牛乳', '奶類', '', 32.00, 100, '../image/奶類/光泉 黑芝麻牛乳.jpg', 1),
('光泉 蘋果牛乳', '奶類', '', 32.00, 100, '../image/奶類/光泉 蘋果牛乳.jpg', 1),
('林鳳營高品質鮮乳 全脂', '奶類', '', 38.00, 100, '../image/奶類/林鳳營高品質鮮乳 全脂.jpg', 1),
('林鳳營高品質鮮乳 低脂', '奶類', '', 38.00, 100, '../image/奶類/林鳳營高品質鮮乳 低脂.jpg', 1),
('金車 健酪乳酸飲料 原味', '奶類', '', 25.00, 100, '../image/奶類/金車 健酪乳酸飲料 原味.jpg', 1),
('金車 健酪乳酸飲料 草莓酪酪', '奶類', '', 25.00, 100, '../image/奶類/金車 健酪乳酸飲料 草莓酪酪.jpg', 1),
('瑞穗巧克力牛奶', '奶類', '', 35.00, 100, '../image/奶類/瑞穗巧克力牛奶.jpg', 1),
('瑞穗果汁牛奶', '奶類', '', 35.00, 100, '../image/奶類/瑞穗果汁牛奶.jpg', 1),
('瑞穗麥芽牛奶', '奶類', '', 35.00, 100, '../image/奶類/瑞穗麥芽牛奶.jpg', 1),
('瑞穗蘋果牛奶', '奶類', '', 35.00, 100, '../image/奶類/瑞穗蘋果牛奶.jpg', 1),

-- 巧克力
('77乳加', '巧克力', '', 25.00, 100, '../image/巧克力/77乳加.jpg', 1),
('MMS 牛奶糖衣巧克力', '巧克力', '', 45.00, 100, '../image/巧克力/MMS 牛奶糖衣巧克力.jpg', 1),
('MMS 花生糖衣巧克力', '巧克力', '', 45.00, 100, '../image/巧克力/MMS 花生糖衣巧克力.jpg', 1),
('MMS 焦糖冷萃咖啡糖衣巧克力', '巧克力', '', 45.00, 100, '../image/巧克力/MMS 焦糖冷萃咖啡糖衣巧克力.jpg', 1),
('健達繽紛樂 白巧克力', '巧克力', '', 35.00, 100, '../image/巧克力/健達繽紛樂 白巧克力.jpg', 1),
('健達繽紛樂 黑巧克力', '巧克力', '', 35.00, 100, '../image/巧克力/健達繽紛樂 黑巧克力.jpg', 1),
('健達繽紛樂', '巧克力', '', 35.00, 100, '../image/巧克力/健達繽紛樂.jpg', 1),

-- 糖果
('HARIBO 小熊軟糖', '糖果', '', 35.00, 100, '../image/糖果/HARIBO 小熊軟糖.jpg', 1),
('Skittles 彩虹糖 混合水果口味', '糖果', '', 30.00, 100, '../image/糖果/Skittles 彩虹糖 混合水果口味.jpg', 1),
('盛香珍 Dr.Q零卡蒟蒻 芭樂+荔枝口味', '糖果', '', 30.00, 100, '../image/糖果/盛香珍 Dr.Q零卡蒟蒻 芭樂+荔枝口味.jpg', 1),
('盛香珍 Dr.Q蒟蒻 水蜜桃+白葡萄口味', '糖果', '', 30.00, 100, '../image/糖果/盛香珍 Dr.Q蒟蒻 水蜜桃+白葡萄口味.jpg', 1),
('盛香珍 Dr.Q蒟蒻 百香果口味', '糖果', '', 30.00, 100, '../image/糖果/盛香珍 Dr.Q蒟蒻 百香果口味.jpg', 1),
('盛香珍 Dr.Q蒟蒻 芒果口味', '糖果', '', 30.00, 100, '../image/糖果/盛香珍 Dr.Q蒟蒻 芒果口味.jpg', 1),
('盛香珍 Dr.Q蒟蒻 草莓口味', '糖果', '', 30.00, 100, '../image/糖果/盛香珍 Dr.Q蒟蒻 草莓口味.jpg', 1),
('盛香珍 Dr.Q蒟蒻 荔枝口味', '糖果', '', 30.00, 100, '../image/糖果/盛香珍 Dr.Q蒟蒻 荔枝口味.jpg', 1),
('盛香珍 Dr.Q蒟蒻 葡萄+荔枝口味', '糖果', '', 30.00, 100, '../image/糖果/盛香珍 Dr.Q蒟蒻 葡萄+荔枝口味.jpg', 1),
('盛香珍 Dr.Q蒟蒻 葡萄口味', '糖果', '', 30.00, 100, '../image/糖果/盛香珍 Dr.Q蒟蒻 葡萄口味.jpg', 1),
('森永 嗨啾 水蜜桃口味', '糖果', '', 25.00, 100, '../image/糖果/森永 嗨啾 水蜜桃口味.jpg', 1),
('森永 嗨啾 乳酸飲料口味', '糖果', '', 25.00, 100, '../image/糖果/森永 嗨啾 乳酸飲料口味.jpg', 1),
('森永 嗨啾 青芒果口味', '糖果', '', 25.00, 100, '../image/糖果/森永 嗨啾 青芒果口味.jpg', 1),
('森永 嗨啾 青檸口味', '糖果', '', 25.00, 100, '../image/糖果/森永 嗨啾 青檸口味.jpg', 1),
('森永 嗨啾 草莓口味', '糖果', '', 25.00, 100, '../image/糖果/森永 嗨啾 草莓口味.jpg', 1),
('森永 嗨啾 葡萄口味', '糖果', '', 25.00, 100, '../image/糖果/森永 嗨啾 葡萄口味.jpg', 1),
('Airwaves 冰釀葡萄', '糖果', '', 25.00, 100, '../image/糖果/Airwaves 冰釀葡萄.jpg', 1),
('Airwaves 蜂蜜檸檬', '糖果', '', 25.00, 100, '../image/糖果/Airwaves 蜂蜜檸檬.jpg', 1),
('Airwaves 冰炫薄荷', '糖果', '', 25.00, 100, '../image/糖果/Airwaves 冰炫薄荷.jpg', 1),
('Airwaves 冷萃咖啡', '糖果', '', 25.00, 100, '../image/糖果/Airwaves 冷萃咖啡.jpg', 1),
('Airwaves 紫冰野莓', '糖果', '', 25.00, 100, '../image/糖果/Airwaves 紫冰野莓.jpg', 1),
('Airwaves 超涼薄荷', '糖果', '', 25.00, 100, '../image/糖果/Airwaves 超涼薄荷.jpg', 1),
('Airwaves 微冰藍莓', '糖果', '', 25.00, 100, '../image/糖果/Airwaves 微冰藍莓.jpg', 1),
('Airwaves 極酷薄荷', '糖果', '', 25.00, 100, '../image/糖果/Airwaves 極酷薄荷.jpg', 1),

-- 餅乾
('新貴派 大格酥 芝麻豆奶口味', '餅乾', '', 35.00, 100, '../image/餅乾/新貴派 大格酥 芝麻豆奶口味.jpg', 1),
('新貴派 大格酥 焙烤花生口味', '餅乾', '', 35.00, 100, '../image/餅乾/新貴派 大格酥 焙烤花生口味.jpg', 1),
('新貴派 大格酥 陽光檸檬口味', '餅乾', '', 35.00, 100, '../image/餅乾/新貴派 大格酥 陽光檸檬口味.jpg', 1),
('新貴派 大格酥 經典巧克力口味', '餅乾', '', 35.00, 100, '../image/餅乾/新貴派 大格酥 經典巧克力口味.jpg', 1),
('新貴派 清爽檸檬', '餅乾', '', 35.00, 100, '../image/餅乾/新貴派 清爽檸檬.jpg', 1),
('新貴派 焦糖海鹽', '餅乾', '', 35.00, 100, '../image/餅乾/新貴派 焦糖海鹽.jpg', 1),
('新貴派 黑白巧甜甜圈餅', '餅乾', '', 35.00, 100, '../image/餅乾/新貴派 黑白巧甜甜圈餅.jpg', 1),
('新貴派 酸甜草莓', '餅乾', '', 35.00, 100, '../image/餅乾/新貴派 酸甜草莓.jpg', 1),
('新貴派 濃郁抹茶', '餅乾', '', 35.00, 100, '../image/餅乾/新貴派 濃郁抹茶.jpg', 1),
('新貴派 優質藍莓', '餅乾', '', 35.00, 100, '../image/餅乾/新貴派 優質藍莓.jpg', 1),
('義美 牛奶小泡芙', '餅乾', '', 30.00, 100, '../image/餅乾/義美 牛奶小泡芙.jpg', 1),
('義美 牛奶法蘭酥', '餅乾', '', 35.00, 100, '../image/餅乾/義美 牛奶法蘭酥.jpg', 1),
('義美 巧克力法蘭酥', '餅乾', '', 35.00, 100, '../image/餅乾/義美 巧克力法蘭酥.jpg', 1),
('義美 芝麻蛋捲', '餅乾', '', 35.00, 100, '../image/餅乾/義美 芝麻蛋捲.jpg', 1),
('義美 花生煎餅', '餅乾', '', 35.00, 100, '../image/餅乾/義美 花生煎餅.jpg', 1),
('義美 香檸法蘭酥', '餅乾', '', 35.00, 100, '../image/餅乾/義美 香檸法蘭酥.jpg', 1),
('義美 海苔煎餅', '餅乾', '', 35.00, 100, '../image/餅乾/義美 海苔煎餅.jpg', 1),
('義美 純麥取向蘇打餅乾', '餅乾', '', 35.00, 100, '../image/餅乾/義美 純麥取向蘇打餅乾.jpg', 1),
('義美 草莓小泡芙', '餅乾', '', 30.00, 100, '../image/餅乾/義美 草莓小泡芙.jpg', 1),
('義美 起司取向蘇打餅乾', '餅乾', '', 35.00, 100, '../image/餅乾/義美 起司取向蘇打餅乾.jpg', 1),
('義美 雙果仁煎餅', '餅乾', '', 35.00, 100, '../image/餅乾/義美 雙果仁煎餅.jpg', 1),
('義美 草莓法蘭酥', '餅乾', '', 35.00, 100, '../image/餅乾/義美 草莓法蘭酥.jpg', 1),
('蜜蘭諾 千層鬆塔', '餅乾', '', 45.00, 100, '../image/餅乾/蜜蘭諾 千層鬆塔.jpg', 1),
('蜜蘭諾 杏仁鬆塔', '餅乾', '', 45.00, 100, '../image/餅乾/蜜蘭諾 杏仁鬆塔.jpg', 1),
('蜜蘭諾 幸福歐風 黑白巧雙重奏', '餅乾', '', 45.00, 100, '../image/餅乾/蜜蘭諾 幸福歐風 黑白巧雙重奏.jpg', 1),
('蜜蘭諾 幸福歐風 濃厚可可酥', '餅乾', '', 45.00, 100, '../image/餅乾/蜜蘭諾 幸福歐風 濃厚可可酥.jpg', 1),
('蜜蘭諾 楓糖葡萄鬆塔', '餅乾', '', 45.00, 100, '../image/餅乾/蜜蘭諾 楓糖葡萄鬆塔.jpg', 1),
('蜜蘭諾 醇黑鬆塔', '餅乾', '', 45.00, 100, '../image/餅乾/蜜蘭諾 醇黑鬆塔.jpg', 1),
('樂天 巧克力派', '餅乾', '', 35.00, 100, '../image/餅乾/樂天 巧克力派.jpg', 1),
('樂天 蛋黃派', '餅乾', '', 35.00, 100, '../image/餅乾/樂天 蛋黃派.jpg', 1),

-- 膨化零食
('孔雀香酥脆 香魚', '膨化零食', '', 25.00, 100, '../image/膨化零食/孔雀香酥脆 香魚.jpg', 1),
('孔雀香酥脆 櫻花蝦口味', '膨化零食', '', 25.00, 100, '../image/膨化零食/孔雀香酥脆 櫻花蝦口味.jpg', 1),
('卡廸那 享自然芋頭片', '膨化零食', '', 35.00, 100, '../image/膨化零食/卡廸那 享自然芋頭片.jpg', 1),
('卡廸那 洋芋片 牛排口味', '膨化零食', '', 35.00, 100, '../image/膨化零食/卡廸那 洋芋片 牛排口味.jpg', 1),
('卡廸那 德州薯條 茄汁口味', '膨化零食', '', 35.00, 100, '../image/膨化零食/卡廸那 德州薯條 茄汁口味.jpg', 1),
('卡廸那 德州薯條 切達起司口味', '膨化零食', '', 35.00, 100, '../image/膨化零食/卡廸那 德州薯條 切達起司口味.jpg', 1),
('可樂果 古早味', '膨化零食', '', 25.00, 100, '../image/膨化零食/可樂果 古早味.jpg', 1),
('可樂果 原味', '膨化零食', '', 25.00, 100, '../image/膨化零食/可樂果 原味.jpg', 1),
('多力多滋 川味花椒雞口味', '膨化零食', '', 49.00, 100, '../image/膨化零食/多力多滋 川味花椒雞口味.jpg', 1),
('多力多滋 皮蛋口味', '膨化零食', '', 49.00, 100, '../image/膨化零食/多力多滋 皮蛋口味.jpg', 1),
('多力多滋 美式辣起司口味', '膨化零食', '', 49.00, 100, '../image/膨化零食/多力多滋 美式辣起司口味.jpg', 1),
('多力多滋 香菜口味', '膨化零食', '', 49.00, 100, '../image/膨化零食/多力多滋 香菜口味.jpg', 1),
('多力多滋 超濃起司口味', '膨化零食', '', 49.00, 100, '../image/膨化零食/多力多滋 超濃起司口味.jpg', 1),
('多力多滋 黃金起司口味', '膨化零食', '', 49.00, 100, '../image/膨化零食/多力多滋 黃金起司口味.jpg', 1),
('多力多滋 蒜味牛菲力口味', '膨化零食', '', 49.00, 100, '../image/膨化零食/多力多滋 蒜味牛菲力口味.jpg', 1),
('多力多滋 爆蒜酷辣口味', '膨化零食', '', 49.00, 100, '../image/膨化零食/多力多滋 爆蒜酷辣口味.jpg', 1),
('多力多滋 爆蒜鮮蝦口味', '膨化零食', '', 49.00, 100, '../image/膨化零食/多力多滋 爆蒜鮮蝦口味.jpg', 1),
('乖乖 5香口味', '膨化零食', '', 25.00, 100, '../image/膨化零食/乖乖 5香口味.jpg', 1),
('乖乖 奶油椰子口味', '膨化零食', '', 25.00, 100, '../image/膨化零食/乖乖 奶油椰子口味.jpg', 1),
('乖乖 香濃巧克力口味', '膨化零食', '', 25.00, 100, '../image/膨化零食/乖乖 香濃巧克力口味.jpg', 1),
('乖乖 彎的脆果 草莓煉乳口味', '膨化零食', '', 25.00, 100, '../image/膨化零食/乖乖 彎的脆果 草莓煉乳口味.jpg', 1),
('乖乖 彎的脆果 煉乳口味', '膨化零食', '', 25.00, 100, '../image/膨化零食/乖乖 彎的脆果 煉乳口味.jpg', 1),
('奇多 2倍濃起司口味', '膨化零食', '', 35.00, 100, '../image/膨化零食/奇多 2倍濃起司口味.jpg', 1),
('奇多 家常起司口味', '膨化零食', '', 35.00, 100, '../image/膨化零食/奇多 家常起司口味.jpg', 1),
('奇多 濃厚海苔口味', '膨化零食', '', 35.00, 100, '../image/膨化零食/奇多 濃厚海苔口味.jpg', 1),
('科學麵 香蔥雞汁風味', '膨化零食', '', 12.00, 100, '../image/膨化零食/科學麵 香蔥雞汁風味.jpg', 1),
('科學麵', '膨化零食', '', 12.00, 100, '../image/膨化零食/科學麵.jpg', 1),
('華元 玉黍叔 漢堡口味', '膨化零食', '', 25.00, 100, '../image/膨化零食/華元 玉黍叔 漢堡口味.jpg', 1),
('華元 玉黍叔玉米條 雙倍辣起司風味', '膨化零食', '', 25.00, 100, '../image/膨化零食/華元 玉黍叔玉米條 雙倍辣起司風味.jpg', 1),
('華元 波的多洋芋片 蚵仔煎口味', '膨化零食', '', 35.00, 100, '../image/膨化零食/華元 波的多洋芋片 蚵仔煎口味.jpg', 1),
('華元 鹹蔬餅歡樂包', '膨化零食', '', 25.00, 100, '../image/膨化零食/華元 鹹蔬餅歡樂包.jpg', 1),
('滿天星 洋芋脆片', '膨化零食', '', 25.00, 100, '../image/膨化零食/滿天星 洋芋脆片.jpg', 1),
('維力 張君雅小妹妹 五香海苔休閒丸子', '膨化零食', '', 30.00, 100, '../image/膨化零食/維力 張君雅小妹妹 五香海苔休閒丸子.jpg', 1),
('維力 張君雅小妹妹 日式串燒休閒丸子', '膨化零食', '', 30.00, 100, '../image/膨化零食/維力 張君雅小妹妹 日式串燒休閒丸子.jpg', 1),
('維力 張君雅小妹妹 和風雞汁拉麵條餅', '膨化零食', '', 30.00, 100, '../image/膨化零食/維力 張君雅小妹妹 和風雞汁拉麵條餅.jpg', 1),
('維力 張君雅小妹妹 麻辣燙風味條餅', '膨化零食', '', 30.00, 100, '../image/膨化零食/維力 張君雅小妹妹 麻辣燙風味條餅.jpg', 1),
('維力 張君雅小妹妹 黑胡椒牛排風味拉麵條餅', '膨化零食', '', 30.00, 100, '../image/膨化零食/維力 張君雅小妹妹 黑胡椒牛排風味拉麵條餅.jpg', 1),
('維力 張君雅小妹妹 韓式炸雞拉麵條餅', '膨化零食', '', 30.00, 100, '../image/膨化零食/維力 張君雅小妹妹 韓式炸雞拉麵條餅.jpg', 1),
('樂事 洋芋片 九州岩燒海苔口味', '膨化零食', '', 35.00, 100, '../image/膨化零食/樂事 洋芋片 九州岩燒海苔口味.jpg', 1),
('樂事 洋芋片 青檸享清新口味', '膨化零食', '', 35.00, 100, '../image/膨化零食/樂事 洋芋片 青檸享清新口味.jpg', 1),
('樂事 洋芋片 美國經典原味', '膨化零食', '', 35.00, 100, '../image/膨化零食/樂事 洋芋片 美國經典原味.jpg', 1),
('樂事 洋芋片 頂級日曬甘味湖鹽口味', '膨化零食', '', 35.00, 100, '../image/膨化零食/樂事 洋芋片 頂級日曬甘味湖鹽口味.jpg', 1),
('樂事 洋芋片 極品香煎干貝口味', '膨化零食', '', 35.00, 100, '../image/膨化零食/樂事 洋芋片 極品香煎干貝口味.jpg', 1),
('樂事 洋芋片 瑞士香濃起司口味', '膨化零食', '', 35.00, 100, '../image/膨化零食/樂事 洋芋片 瑞士香濃起司口味.jpg', 1);


-- 進貨資料表
CREATE TABLE IF NOT EXISTS `receiving_headers` (
  `receiving_id` int NOT NULL AUTO_INCREMENT,
  `supplier_name` varchar(100) DEFAULT NULL,
  `total_lines` int NOT NULL DEFAULT 1,
  `total_cost` decimal(10,2) DEFAULT NULL,
  `note` text DEFAULT NULL,
  `received_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_by_admin_id` int DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`receiving_id`),
  KEY `idx_received_at` (`received_at`),
  CONSTRAINT `fk_receiving_admin` FOREIGN KEY (`created_by_admin_id`) REFERENCES `members`(`member_id`) ON DELETE SET NULL
) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;


-- 進貨項目資料表
CREATE TABLE IF NOT EXISTS `receiving_items` (
  `receiving_item_id` int NOT NULL AUTO_INCREMENT,
  `receiving_id` int NOT NULL,
  `product_id` int NOT NULL,
  `qty` int NOT NULL,
  `unit_cost` decimal(10,2) DEFAULT NULL,
  `subtotal_cost` decimal(10,2) DEFAULT NULL,
  PRIMARY KEY (`receiving_item_id`),
  KEY `idx_receiving_id` (`receiving_id`),
  KEY `idx_product_id` (`product_id`),
  CONSTRAINT `fk_receiving_item_header` FOREIGN KEY (`receiving_id`) REFERENCES `receiving_headers`(`receiving_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_receiving_item_product` FOREIGN KEY (`product_id`) REFERENCES `products`(`product_id`) ON DELETE RESTRICT
) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;


-- 庫存異動資料表
CREATE TABLE IF NOT EXISTS `stock_movements` (
  `movement_id` int NOT NULL AUTO_INCREMENT,
  `product_id` int NOT NULL,
  `movement_type` enum('receive','ship','adjust') NOT NULL,
  `delta` int NOT NULL,
  `ref_type` enum('receiving','order','manual') NOT NULL,
  `ref_id` int DEFAULT NULL,
  `note` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`movement_id`),
  KEY `idx_movement_product` (`product_id`),
  KEY `idx_movement_created` (`created_at`),
  CONSTRAINT `fk_movement_product` FOREIGN KEY (`product_id`) REFERENCES `products`(`product_id`) ON DELETE RESTRICT
) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;


-- 訂單資料表
CREATE TABLE `orders` (
  `order_id` int(11) NOT NULL AUTO_INCREMENT,
  `member_id` int(11) NOT NULL,
  `address_id` int(11) DEFAULT NULL,

  `ship_name` varchar(255) NOT NULL,
  `ship_phone` varchar(50) DEFAULT NULL,
  `ship_address_line` varchar(500) NOT NULL,

  `total_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `status` enum('pending','accepted','preparing','shipping','done') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`order_id`),
  KEY `member_id` (`member_id`),
  KEY `address_id` (`address_id`),
  CONSTRAINT `fk_order_member`
    FOREIGN KEY (`member_id`) REFERENCES `members`(`member_id`)
    ON DELETE NO ACTION,
  CONSTRAINT `fk_order_address`
    FOREIGN KEY (`address_id`) REFERENCES `member_addresses`(`address_id`)
    ON DELETE SET NULL
) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;


-- 訂單項目資料表
CREATE TABLE `order_items` (
  `order_item_id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `unit_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`order_item_id`),
  KEY `order_id` (`order_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `fk_item_order`
    FOREIGN KEY (`order_id`) REFERENCES `orders`(`order_id`)
    ON DELETE CASCADE,
  CONSTRAINT `fk_item_product`
    FOREIGN KEY (`product_id`) REFERENCES `products`(`product_id`)
    ON DELETE RESTRICT
) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;


-- 密碼重設令牌表
CREATE TABLE `password_resets` (
  `reset_id` int(11) NOT NULL AUTO_INCREMENT,
  `member_id` int(11) NOT NULL,
  `token` varchar(255) NOT NULL UNIQUE,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`reset_id`),
  FOREIGN KEY (`member_id`) REFERENCES `members`(`member_id`) ON DELETE CASCADE
) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;


COMMIT;
