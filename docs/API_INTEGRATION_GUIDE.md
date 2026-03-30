# VR Mall 后台管理系统 - API 集成指南

## 🎯 快速开始

### 1. 访问营运后台
```
后台地址: http://localhost/VR%20shop/views/operations.php
需要管理员登录
```

### 2. 导入数据库
```bash
# 在 MySQL 中执行
mysql -u root -p vr_mall < "c:\xampp\htdocs\VR shop\database\enhanced_tables.sql"
```

---

## 📚 常见场景 API 调用示例

### 场景 1: 创建促销活动并生成优惠代码

```javascript
// 创建促销活动
async function createPromotion() {
    const promoData = {
        title: '春节大促',
        description: '全场产品 8.8 折',
        promotion_type: 'global_discount',
        discount_type: 'percent',
        discount_value: 12,
        min_order_amount: 500,
        start_date: '2026-03-22 00:00:00',
        end_date: '2026-03-31 23:59:59',
        is_active: 1
    };
    
    const response = await fetch('../api/operations.php?action=create_promotion', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: new URLSearchParams(promoData)
    });
    
    const result = await response.json();
    console.log('创建的促销 ID:', result.promotion_id);
    
    return result.promotion_id;
}

// 为促销生成优惠代码
async function generateCode(promotionId) {
    const response = await fetch(
        '../api/operations.php?action=generate_promo_code',
        {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: new URLSearchParams({
                promotion_id: promotionId,
                code: 'SPRING2026',
                usage_limit: 1000
            })
        }
    );
    
    const result = await response.json();
    console.log('生成的代码:', result.code);
}

// 调用
const promoId = await createPromotion();
await generateCode(promoId);
```

---

### 场景 2: 客户分层与标签管理

```javascript
// 获取所有客户及其标签
async function loadCustomersWithLabels() {
    const response = await fetch(
        '../api/operations.php?action=list_customers_with_labels'
    );
    const customers = await response.json();
    
    // 按标签分类
    const viCustomers = customers.filter(c => c.labels && c.labels.includes('VIP'));
    const highValueCustomers = customers.filter(c => 
        c.labels && c.labels.includes('高单价')
    );
    
    console.log('VIP 客户:', viCustomers.length);
    console.log('高单价客户:', highValueCustomers.length);
    
    return customers;
}

// 为客户添加标签
async function labelCustomerAsVip(customerId) {
    const response = await fetch(
        '../api/operations.php?action=add_customer_label',
        {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: new URLSearchParams({
                member_id: customerId,
                label: 'VIP'
            })
        }
    );
    
    const result = await response.json();
    console.log('标签添加结果:', result);
}

// 调用
const customers = await loadCustomersWithLabels();
const vipCustomer = customers[0];
await labelCustomerAsVip(vipCustomer.member_id);
```

---

### 场景 3: 监控销售数据和漏斗

```javascript
// 获取销售仪表板指标
async function getSalesDashboard() {
    const response = await fetch(
        '../api/operations.php?action=get_sales_dashboard'
    );
    const data = await response.json();
    
    console.log('=== 销售看板 ===');
    console.log('今日营业额:', data.today?.revenue || 0);
    console.log('本月销售:', data.this_month?.revenue || 0);
    console.log('平均客单价:', data.avg_order_value);
    console.log('订单数:', data.total_orders);
    
    return data;
}

// 获取转化漏斗
async function getConversionFunnel() {
    const response = await fetch(
        '../api/operations.php?action=get_conversion_funnel'
    );
    const funnel = await response.json();
    
    console.log('=== 转化漏斗 ===');
    funnel.forEach((stage, index) => {
        console.log(`${index + 1}. ${stage.stage}: ${stage.count} (${stage.conversion_rate}%)`);
    });
    
    return funnel;
}

// 获取流量来源分析
async function getTrafficSources() {
    const response = await fetch(
        '../api/operations.php?action=get_traffic_sources'
    );
    const sources = await response.json();
    
    console.log('=== 流量来源 ===');
    sources.forEach(source => {
        console.log(
            `${source.source}: ${source.order_count} 订单, ` +
            `$${source.revenue} 收入, ${source.conversion_rate}% 转化率`
        );
    });
    
    return sources;
}

// 调用
await getSalesDashboard();
await getConversionFunnel();
await getTrafficSources();
```

---

### 场景 4: 棉单挽救系统

```javascript
// 获取棉单列表
async function getAbandonedCarts() {
    const response = await fetch(
        '../api/operations.php?action=get_abandoned_carts'
    );
    const carts = await response.json();
    
    console.log('=== 棉单列表 ===');
    carts.forEach(cart => {
        console.log(
            `客户: ${cart.customer_email}，` +
            `金额: $${cart.cart_total}，` +
            `时间: ${cart.abandoned_at}`
        );
    });
    
    return carts;
}

// 自动发送挽救邮件
async function sendAbandonedCartEmails() {
    const carts = await getAbandonedCarts();
    
    for (const cart of carts) {
        // 检查是否超过 24 小时
        const abandonedTime = new Date(cart.abandoned_at);
        const nowTime = new Date();
        const hoursPassed = (nowTime - abandonedTime) / (1000 * 60 * 60);
        
        if (hoursPassed >= 24) {
            // 发送提醒邮件
            await sendEmail({
                to: cart.customer_email,
                subject: '❌ 您遗漏了购物车中的商品！',
                template: 'abandoned_cart_reminder',
                data: {
                    customer_name: cart.customer_name,
                    cart_amounts: cart.cart_total,
                    recovery_link: `/checkout.php?cart_id=${cart.cart_id}`
                }
            });
            
            console.log(`已发送提醒邮件给: ${cart.customer_email}`);
        }
    }
}
```

---

### 场景 5: 库存预警系统

```javascript
// 获取库存预警
async function getInventoryAlerts() {
    const response = await fetch(
        '../api/operations.php?action=get_inventory_alerts'
    );
    const alerts = await response.json();
    
    console.log('=== 库存预警 ===');
    alerts.forEach(alert => {
        console.log(
            `商品: ${alert.product_name}，` +
            `当前库存: ${alert.current_stock}，` +
            `预警阈值: ${alert.alert_threshold}`
        );
    });
    
    return alerts;
}

// 处理库存预警
async function resolveInventoryAlert(alertId) {
    const response = await fetch(
        '../api/operations.php?action=resolve_inventory_alert',
        {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: new URLSearchParams({
                alert_id: alertId,
                action: 'ordered',  // 已下单采购
                quantity: 100
            })
        }
    );
    
    const result = await response.json();
    console.log('预警处理:', result);
}

// 调用
const alerts = await getInventoryAlerts();
if (alerts.length > 0) {
    await resolveInventoryAlert(alerts[0].alert_id);
}
```

---

### 场景 6: 商品规格管理

```javascript
// 创建商品规格
async function createProductVariant() {
    const variantData = {
        product_id: 123,
        variant_name: '红色-M号',
        color: 'red',
        size: 'M',
        stock: 50,
        sku: 'VR-RED-M-001',
        price_offset: 0  // 相对于基价的偏移
    };
    
    const response = await fetch(
        '../api/operations.php?action=create_product_variant',
        {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: new URLSearchParams(variantData)
        }
    );
    
    const result = await response.json();
    console.log('创建的规格 ID:', result.variant_id);
    
    return result;
}

// 获取商品的所有规格
async function listProductVariants(productId) {
    const response = await fetch(
        `../api/operations.php?action=list_product_variants&product_id=${productId}`
    );
    const variants = await response.json();
    
    console.log(`商品 ${productId} 的所有规格:`);
    variants.forEach(variant => {
        console.log(
            `- ${variant.variant_name}: ` +
            `${variant.color}/${variant.size}, ` +
            `库存: ${variant.stock}`
        );
    });
    
    return variants;
}

// 调用
await createProductVariant();
await listProductVariants(123);
```

---

### 场景 7: 物流订单管理

```javascript
// 创建物流订单
async function createLogisticsOrder(orderId) {
    const logisticsData = {
        order_id: orderId,
        logistics_provider: '顺丰速运',
        recipient_name: '张三',
        recipient_phone: '13800138000',
        recipient_address: '北京市朝阳区 XX 街道 XX 号',
        logistics_fee: 20
    };
    
    const response = await fetch(
        '../api/operations.php?action=create_logistics_order',
        {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: new URLSearchParams(logisticsData)
        }
    );
    
    const result = await response.json();
    console.log('物流单创建成功，跟踪号:', result.tracking_number);
    
    return result;
}

// 获取物流订单列表
async function listLogisticsOrders() {
    const response = await fetch(
        '../api/operations.php?action=list_logistics_orders'
    );
    const orders = await response.json();
    
    console.log('=== 物流订单列表 ===');
    orders.forEach(order => {
        console.log(
            `订单 ${order.order_id}: ` +
            `${order.logistics_provider} ` +
            `跟踪号: ${order.tracking_number}`
        );
    });
    
    return orders;
}

// 调用
const logisticsOrder = await createLogisticsOrder(1001);
await listLogisticsOrders();
```

---

### 场景 8: 商品排行分析

```javascript
// 获取商品排行
async function getProductRanking(orderBy = 'revenue') {
    const response = await fetch(
        `../api/operations.php?action=get_product_ranking&order_by=${orderBy}`
    );
    const ranking = await response.json();
    
    console.log(`=== 商品排行 (按 ${orderBy}) ===`);
    ranking.forEach((product, index) => {
        console.log(
            `${index + 1}【${product.product_name}】 ` +
            `销售额: $${product.revenue}，` +
            `销量: ${product.quantity}，` +
            `订单数: ${product.order_count}`
        );
    });
    
    return ranking;
}

// 调用
await getProductRanking('revenue');  // 按销售额排序
await getProductRanking('quantity');  // 按销量排序
```

---

## 🔌 集成检查清单

- [ ] 导入 `enhanced_tables.sql` 到 MySQL
- [ ] 测试 `/api/operations.php` 各个端点
- [ ] 在客户端集成 sales dashboard 展示
- [ ] 配置 Google Analytics 4、Facebook Pixel、GTM
- [ ] 设置棉单挽救邮件任务（计划任务）
- [ ] 创建库存预警通知流程
- [ ] 配置促销活动管理界面
- [ ] 部署客户标签自动化规则
- [ ] 测试转化漏斗追踪
- [ ] 监控流量来源归因准确性

---

## 🐛 常见问题

### Q: API 返回 404 错误
**A:** 检查以下条件：
1. 文件 `/api/operations.php` 是否存在
2. MySQL 连接是否正常
3. 会话是否登录（`$_SESSION['user_id']`）

### Q: 数据表显示为空
**A:** 
1. 执行 `enhanced_tables.sql` 导入表结构
2. 检查表中是否有数据：`SELECT COUNT(*) FROM table_name;`

### Q: 追踪代码不工作
**A:**
1. 确认在 operations.php 后台配置了 ID
2. 检查浏览器控制台是否有 JavaScript 错误
3. 使用浏览器插件验证 pixel 是否触发

### Q: 如何导出销售报告？
**A:** 直接使用 SQL 查询，例如：
```sql
SELECT 
    DATE(created_at) as date,
    COUNT(*) as order_count,
    SUM(total_amount) as revenue
FROM orders
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
GROUP BY DATE(created_at)
ORDER BY date DESC;
```

---

## 📊 数据库关系图

```
orders (订单表)
├── order_items (订单项目)
├── payment_transactions (支付记录)
└── logistics_orders (物流单)

products (商品表)
├── product_variants (商品规格)
└── inventory_alerts (库存预警)

members (客户表)
├── customer_labels_mapping (客户标签)
│   └── customer_labels (标签定义)
└── abandoned_carts (棉单)

promotions (活动表)
└── promo_codes (优惠券代码)

analytics_events (分析事件)
```

---

## 🚀 性能优化建议

1. **缓存仪表板数据** - 缓存 `get_sales_dashboard` 结果 5 分钟
2. **异步处理棉单邮件** - 使用队列而不是同步发送
3. **索引关键查询** - 在 `member_id`, `created_at`, `status` 上创建索引
4. **定期清理日志** - 清理超过 90 天的 `analytics_events`

---

**文档版本:** 2.0  
**最后更新:** 2026-03-22  
**维护人:** 开发团队
