# VR Mall 电商后台管理系统 v2.0
## 核心营运模块 (Core Operations) 实现文档

---

## 📋 系统概览

本文档详细说明了全新的电商后台管理系统，包含三大核心功能区：
1. **核心营运模块** - PIM、OMS、CRM、物流金流
2. **行銷與轉化工具** - 促销、棄單挽救、導購追蹤
3. **數據分析與可視化** - 銷售看板、商品排行、漏斗分析

---

## 1️⃣ 核心營運模組 (Core Operations)

### 1.1 商品管理系統 (PIM - Product Information Management)

#### 功能特性：
- **批次上架** - 支持一次上架多个商品
- **規格組合** - 支持颜色、尺寸等多维度规格
- **庫存預警** - 自動監測低庫存商品
- **自動下架** - 當商品滯銷或過期時自動停用

#### 数据库表：
```sql
products          -- 基础商品信息
product_variants  -- 商品规格（颜色、尺寸等）
inventory_alerts  -- 库存预警日志
```

#### API 端點：
```
POST /api/operations.php?action=list_product_variants
POST /api/operations.php?action=create_product_variant
POST /api/operations.php?action=update_product_variant
GET  /api/operations.php?action=get_inventory_alerts
POST /api/operations.php?action=resolve_inventory_alert
```

#### 使用示例：
```javascript
// 創建產品規格變體
const variant = await opsApi.post('../api/operations.php?action=create_product_variant', {
    product_id: 1,
    variant_name: '紅色-M',
    color: 'red',
    size: 'M',
    stock: 50,
    price_offset: 0
});

// 獲取庫存預警
const alerts = await opsApi.get('../api/operations.php?action=get_inventory_alerts');
```

---

### 1.2 訂單管理系統 (OMS - Order Management System)

#### 功能特性：
- **多通路訂單整合** - 統一管理所有訂單
- **自動狀態更新** - 訂單自動流轉（待付款 → 出貨中 → 已完成）
- **退貨管理** - 支持退貨流程追踪
- **訂單查詢** - 完整的訂單搜尋和篩選

#### 数据库表：
```sql
orders           -- 訂單主表
order_items      -- 訂單項目明細
logistics_orders -- 物流信息
payment_transactions -- 付款記錄
```

#### API 端點：
```
GET  /api/admin.php?action=list_orders
POST /api/admin.php?action=update_order_status
POST /api/admin.php?action=delete_order
```

---

### 1.3 物流與金流串接 (Logistics & Payment Integration)

#### 功能特性：
- **自動產生物流單號** - 支持超商取貨、黑貓宅配等
- **追蹤號管理** - 完整的物流追踪
- **自動對帳功能** - 自動核對支付狀態
- **多支付通道** - 信用卡、貨到付款等對帳管理

#### 数据库表：
```sql
logistics_orders       -- 物流單號
payment_transactions   -- 支付交易記錄
```

#### API 端點：
```
POST /api/operations.php?action=create_logistics_order
GET  /api/operations.php?action=list_logistics_orders
```

#### 使用示例：
```javascript
// 創建物流訂單
const logistics = await opsApi.post('../api/operations.php?action=create_logistics_order', {
    order_id: 1,
    logistics_provider: '黑貓宅配',
    tracking_number: '1234567890',
    logistics_fee: 100
});
```

---

### 1.4 客戶管理 (CRM - Customer Relationship Management)

#### 功能特性：
- **購買紀錄** - 完整記錄每位顧客的購買歷史
- **客戶標籤** - 標籤管理（VIP、高單價、流失客户等）
- **客戶分層** - 自動識別不同價值客戶
- **優惠券發放** - 針對性地發送優惠券或電子報

#### 数据库表：
```sql
customer_labels          -- 客戶標籤定義
customer_label_mapping   -- 客戶標籤對應
```

#### API 端點：
```
GET  /api/operations.php?action=list_customers_with_labels
GET  /api/operations.php?action=get_customer_profile
POST /api/operations.php?action=add_customer_label
POST /api/operations.php?action=remove_customer_label
```

#### 客戶標籤類型（預設）：
- 🏆 **VIP** - VIP 高級客戶
- 💰 **高單價** - 單筆訂單金額高
- 👻 **流失客戶** - 很久未購買
- 🆕 **新客戶** - 首次購買
- 🔄 **常購客戶** - 頻繁購買

---

## 2️⃣ 行銷與轉化工具 (Marketing & Conversion)

### 2.1 多元折扣引擎 (Promotion Engine)

#### 支持的折扣類型：
| 類型 | 描述 | 例子 |
|-----|------|------|
| 全館折扣 | 所有商品統一優惠 | 全場 9 折 |
| 滿額折扣 | 滿指定金額後折扣 | 滿 $500 折 $50 |
| 加價購 | 另買享優惠 | 買主食加 $49 享甜點 |
| 組合價 | 多件買更便宜 | 3 件組合價 $999 |
| 限定優惠代碼 | 使用代碼享折扣 | 代碼 LUCKY20 折 20% |
| 滿額免運 | 達到金額免運費 | 滿 $1000 免運 |

#### 数据库表：
```sql
promotions   -- 促銷活動主表
promo_codes  -- 優惠券代碼
```

#### API 端點：
```
GET  /api/operations.php?action=list_promotions
POST /api/operations.php?action=create_promotion
POST /api/operations.php?action=update_promotion
POST /api/operations.php?action=generate_promo_code
```

#### 使用示例：
```javascript
// 創建全館 9 折促銷
const promo = await opsApi.post('../api/operations.php?action=create_promotion', {
    title: '新年大販売',
    promotion_type: 'global_discount',
    discount_type: 'percent',
    discount_value: 10,
    start_date: '2026-03-22 00:00:00',
    end_date: '2026-03-31 23:59:59'
});

// 生成優惠代碼
const code = await opsApi.post('../api/operations.php?action=generate_promo_code', {
    promotion_id: promo.promotion_id,
    code: 'LUCKY20',
    usage_limit: 100
});
```

---

### 2.2 棄單挽回系統 (Abandoned Cart Recovery)

#### 功能流程：
1. **監測棄單** - 自動記錄未完成的購物車
2. **自動發送** - 定時發送提醒郵件（24小時、48小時、7天）
3. **追蹤效果** - 記錄挽回次數和成功率
4. **改進策略** - 根據數據調整挽回時機和內容

#### 数据库表：
```sql
abandoned_carts -- 棄單記錄
```

#### 棄單挽回流程：
```
購物車建立 → 檢測到棄單 → 發送第1次提醒 (24小時)
                    → 發送第2次提醒 (48小時)
                    → 發送第3次提醒 (7天)
                    → 標記為已挽回或已放棄
```

#### API 端點：
```
GET /api/operations.php?action=get_abandoned_carts
```

#### 自動發送郵件的建議內容：
```
主旨：❌ 您遺漏了購物車中的商品！

親愛的 [客戶名稱]，

我們發現您在 [棄單時間] 未完成結帳。
您的購物車中有 [商品數量] 件商品，總額為 $[金額]

現在完成購買可享受：
- 👉 立即結帳連結
- 💝 額外 10% 折扣（限本次）
- 🎁 VIP 免運優惠

```

---

### 2.3 導購追蹤 (Tracking & Attribution)

#### 支持的追蹤工具：
- **Google Analytics 4 (GA4)** - 詳細的用戶行為分析
- **Facebook Pixel** - 廣告轉換追蹤和受眾構建
- **Google Tag Manager (GTM)** - 無需編碼的標籤管理

#### 追蹤事件類型：
```
page_view         - 頁面瀏覽
product_view      - 查看商品
add_to_cart       - 加入購物車
checkout_start    - 開始結帳
purchase          - 完成購買
```

#### 埋設方式（由後台自動完成）：
```html
<!-- Google Analytics 4 -->
<script async src="https://www.googletagmanager.com/gtag/js?id=G-XXXXXXXXXX"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());
  gtag('config', 'G-XXXXXXXXXX');
</script>

<!-- Facebook Pixel -->
<img height="1" width="1" style="display:none"
  src="https://www.facebook.com/tr?id=123456789&ev=PageView&noscript=1" />

<!-- Google Tag Manager -->
<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
})(window,document,'script','dataLayer','GTM-XXXXXXX');</script>
```

---

## 3️⃣ 數據分析與可視化 (Data & Insights)

### 3.1 銷售看板 (Sales Dashboard)

#### 核心指標：
| 指標 | 說明 | 計算方式 |
|-----|------|--------|
| 今日營業額 | 今天總銷售額 | SUM(orders.total_amount) WHERE DATE(created_at) = TODAY() |
| 本月銷售額 | 本月累計銷售額 | SUM(orders.total_amount) WHERE MONTH/YEAR = NOW |
| 平均客單價 | 平均每筆訂單金額 | AVG(orders.total_amount) |
| 訂單數 | 已生成訂單總數 | COUNT(orders) |

#### 數據展示：
- 📊 今日 vs 昨日對比
- 📈 最近 7 天營業額趨勢圖
- 📋 待處理訂單數
- ⚠️ 庫存預警商品數

#### 實現代碼：
```javascript
// 獲取銷售看板數據
async function loadSalesDashboard() {
    const data = await fetch('../api/operations.php?action=get_sales_dashboard')
        .then(r => r.json());
    
    const today = data.today || {};
    const month = data.this_month || {};
    
    document.getElementById('todayRevenue').textContent = '$' + (today.revenue || 0);
    document.getElementById('monthRevenue').textContent = '$' + (month.revenue || 0);
}
```

---

### 3.2 商品排行 (Product Ranking Analysis)

#### 排行維度：
1. **按銷售額排序** - 找出「帶貨王」商品
2. **按銷量排序** - 最受歡迎的商品
3. **按利潤排序** - 最賺錢的商品
4. **按滯銷排序** - 需要推廣的商品

#### 數據展示：
```
排名  商品名稱              銷售數量  訂單數  銷售額
1     紅色咖啡杯 (M)        5,234    1,245   $150,000
2     藍色咖啡杯 (L)        4,892    1,102   $142,000
3     環保購物袋            3,456    892     $98,500
...
```

#### 使用場景：
- 📦 優化進貨策略 - 暢銷品多進，滯銷品停止進
- 🎯 重點推廣 - 針對中等銷售商品進行組合優惠
- 🔄 清庫存 - 識別積壓商品並進行折扣清理

#### API 端點：
```
GET /api/operations.php?action=get_product_ranking
```

---

### 3.3 來源分析 (Traffic & Conversion Sources)

#### 追蹤維度：
| 來源 | 說明 | 目標客戶質量 |
|-----|------|-----------|
| facebook | Facebook 流量 | 中～高 |
| google_organic | Google 自然搜尋 | 高 |
| google_ads | Google 廣告 | 中 |
| direct | 直接訪問 | 高 |
| email | 郵件行銷 | 高 |
| social | 其他社交媒體 | 低～中 |

#### 關鍵指標：
```
per_channel_metrics := {
    order_count: 訂單數,
    revenue: 總營收,
    avg_order_value: 客單價,
    conversion_rate: 轉化率,
    customer_roi: 客戶投資回報率
}
```

#### 數據應用：
- 🎯 **廣告預算分配** - 為表現好的渠道增加預算
- 📊 **文案優化** - 針對不同渠道客户優化轉換文案
- 🔍 **歸因分析** - 理解客户的完整購買路徑

---

### 3.4 漏斗分析 (Conversion Funnel Analysis)

#### 轉化漏斗階段：
```
頁面瀏覽 (Page View)
    ↓ (轉化率 X%)
查看商品 (Product View)
    ↓ (轉化率 Y%)
加入購物車 (Add to Cart)
    ↓ (轉化率 Z%)
開始結帳 (Checkout Start)
    ↓ (轉化率 W%)
完成購買 (Purchase)
```

#### 計算公式：
```
Product View Rate = Product Views / Page Views * 100%
Add Cart Rate = Add to Cart / Page Views * 100%
Checkout Rate = Checkout Start / Add to Cart * 100%
Purchase Rate = Purchases / Checkout Start * 100%
Overall Conversion = Purchases / Page Views * 100%
```

#### 流失分析：
```
識別每個階段的流失率
↓
分析流失原因（頁面加載慢？商品信息不清？結帳複雜？）
↓
對症施策（優化頁面、完善描述、簡化流程）
↓
測量改進效果
```

#### 優化建議：
| 問題 | 症狀 | 解決方案 |
|-----|------|--------|
| 頁面加載慢 | Product View 率低 | 優化圖片、啟用CDN |
| 商品信息不足 | Add Cart 率低 | 完善描述、添加視頻 |
| 結帳流程複雜 | Checkout 完成率低 | 簡化表單、一鍵支付 |
| 信任度低 | Purchase 率低 | 添加評論、顯示保證 |

---

## 📂 文件結構

```
VR shop/
├── api/
│   ├── admin.php          # 基礎管理 API
│   └── operations.php     # 營運管理 API（新）
├── views/
│   ├── admin.php          # 基礎管理後台
│   └── operations.php     # 營運管理後台（新）
├── database/
│   ├── vr_mall.sql        # 原始表結構
│   └── enhanced_tables.sql # 增強表結構（新）
└── public/
    └── js/
        └── operations.js  # 營運管理 JS（新）
```

---

## 🚀 部署步驟

### 1. 導入數據庫表
```bash
# 登入 MySQL
mysql -u root -p vr_mall < database/enhanced_tables.sql
```

### 2. 訪問後台
```
後台登入：/views/admin.php
營運後台：/views/operations.php (新)
```

### 3. 設置追蹤代碼
在營運後台 → 導購追蹤中配置：
- Google Analytics 4 ID
- Facebook Pixel ID
- Google Tag Manager ID

---

## ⚡ API 速查表

### CRM 客戶管理
```
GET  /api/operations.php?action=list_customers_with_labels
POST /api/operations.php?action=add_customer_label
POST /api/operations.php?action=remove_customer_label
GET  /api/operations.php?action=get_customer_profile
```

### 促銷管理
```
GET  /api/operations.php?action=list_promotions
POST /api/operations.php?action=create_promotion
POST /api/operations.php?action=update_promotion
POST /api/operations.php?action=generate_promo_code
```

### 數據分析
```
GET /api/operations.php?action=get_sales_dashboard
GET /api/operations.php?action=get_product_ranking
GET /api/operations.php?action=get_conversion_funnel
GET /api/operations.php?action=get_traffic_sources
GET /api/operations.php?action=get_abandoned_carts
```

### 庫存管理
```
GET  /api/operations.php?action=get_inventory_alerts
POST /api/operations.php?action=resolve_inventory_alert
```

### 產品規格
```
GET  /api/operations.php?action=list_product_variants
POST /api/operations.php?action=create_product_variant
POST /api/operations.php?action=update_product_variant
```

### 物流管理
```
POST /api/operations.php?action=create_logistics_order
GET  /api/operations.php?action=list_logistics_orders
```

---

## 📞 支持

如有問題或建議，請聯繫開發團隊。

**版本:** 2.0  
**最後更新:** 2026-03-22
