<?php
/**
 * 电商后台营运管理 API
 * 包含：PIM、OMS、CRM、营销工具、数据分析等
 */
require_once __DIR__ . '/db.php';
$user = require_admin();

$action = $_GET['action'] ?? '';

switch ($action) {
    // ===== CRM & 客户管理 =====
    case 'list_customers_with_labels':
        list_customers_with_labels();
        break;
    case 'add_customer_label':
        add_customer_label();
        break;
    case 'remove_customer_label':
        remove_customer_label();
        break;
    case 'get_customer_profile':
        get_customer_profile();
        break;

    // ===== 促销管理 =====
    case 'list_promotions':
        list_promotions();
        break;
    case 'create_promotion':
        create_promotion();
        break;
    case 'update_promotion':
        update_promotion();
        break;
    case 'generate_promo_code':
        generate_promo_code();
        break;

    // ===== 数据分析 =====
    case 'get_sales_dashboard':
        get_sales_dashboard();
        break;
    case 'get_product_ranking':
        get_product_ranking();
        break;
    case 'get_conversion_funnel':
        get_conversion_funnel();
        break;
    case 'get_traffic_sources':
        get_traffic_sources();
        break;
    case 'get_abandoned_carts':
        get_abandoned_carts();
        break;

    // ===== 库存管理 =====
    case 'get_inventory_alerts':
        get_inventory_alerts();
        break;
    case 'resolve_inventory_alert':
        resolve_inventory_alert();
        break;

    // ===== 商品管理 =====
    case 'list_products':
        list_products();
        break;
    case 'create_product':
        create_product();
        break;
    case 'update_product':
        update_product();
        break;
    case 'delete_product':
        delete_product();
        break;

    // ===== 订单管理 =====
    case 'list_orders':
        list_orders();
        break;

    // ===== 产品规格管理 =====
    case 'list_product_variants':
        list_product_variants();
        break;
    case 'create_product_variant':
        create_product_variant();
        break;
    case 'update_product_variant':
        update_product_variant();
        break;

    // ===== 物流管理 =====
    case 'create_logistics_order':
        create_logistics_order();
        break;
    case 'list_logistics_orders':
        list_logistics_orders();
        break;

    default:
        respond_json(['error' => 'Unknown action'], 400);
}

// ============ 商品管理函数 ============
function list_products() {
    $db = get_db();
    
    $sql = 'SELECT product_id, name, category, price, stock, is_active FROM products ORDER BY product_id DESC';
    $result = $db->query($sql);
    
    if (!$result) {
        respond_json(['error' => 'Query failed'], 500);
        return;
    }
    
    $products = [];
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
    
    respond_json(['success' => true, 'products' => $products]);
}

function create_product() {
    $db = get_db();
    $name = $_POST['name'] ?? '';
    $category = $_POST['category'] ?? '';
    $price = (float)($_POST['price'] ?? 0);
    $stock = (int)($_POST['stock'] ?? 0);
    
    if (empty($name) || $price <= 0) {
        respond_json(['error' => '商品名稱和價格為必填'], 400);
        return;
    }
    
    $sql = 'INSERT INTO products (name, category, price, stock, is_active) VALUES (?, ?, ?, ?, 1)';
    $stmt = $db->prepare($sql);
    $stmt->bind_param('ssdi', $name, $category, $price, $stock);
    
    if ($stmt->execute()) {
        respond_json(['success' => true, 'product_id' => $db->insert_id]);
    } else {
        respond_json(['error' => '新增失敗'], 500);
    }
}

function update_product() {
    $db = get_db();
    $product_id = (int)($_POST['product_id'] ?? 0);
    $name = $_POST['name'] ?? '';
    $category = $_POST['category'] ?? '';
    $price = (float)($_POST['price'] ?? 0);
    $stock = (int)($_POST['stock'] ?? 0);
    
    $sql = 'UPDATE products SET name=?, category=?, price=?, stock=? WHERE product_id=?';
    $stmt = $db->prepare($sql);
    $stmt->bind_param('ssdii', $name, $category, $price, $stock, $product_id);
    
    if ($stmt->execute()) {
        respond_json(['success' => true]);
    } else {
        respond_json(['error' => '更新失敗'], 500);
    }
}

function delete_product() {
    $db = get_db();
    $product_id = (int)($_POST['product_id'] ?? 0);
    
    $sql = 'DELETE FROM order_items WHERE product_id=?';
    $stmt = $db->prepare($sql);
    $stmt->bind_param('i', $product_id);
    $stmt->execute();
    
    $sql = 'DELETE FROM products WHERE product_id=?';
    $stmt = $db->prepare($sql);
    $stmt->bind_param('i', $product_id);
    
    if ($stmt->execute()) {
        respond_json(['success' => true]);
    } else {
        respond_json(['error' => '刪除失敗'], 500);
    }
}

// ============ 订单管理函数 ============
function list_orders() {
    $db = get_db();
    
    $sql = 'SELECT order_id, order_number, member_id, total_amount, status, created_at 
            FROM orders ORDER BY created_at DESC LIMIT 100';
    $result = $db->query($sql);
    
    if (!$result) {
        respond_json(['error' => 'Query failed'], 500);
        return;
    }
    
    $orders = [];
    while ($row = $result->fetch_assoc()) {
        $orders[] = $row;
    }
    
    respond_json(['success' => true, 'orders' => $orders]);
}

// ============ CRM 函数 ============
function list_customers_with_labels() {
    $db = get_db();
    
    $sql = 'SELECT m.member_id, m.name, m.email, m.phone, m.created_at,
            COUNT(DISTINCT o.order_id) as order_count,
            COALESCE(SUM(o.total_amount), 0) as total_spent,
            GROUP_CONCAT(DISTINCT cl.label_name) as labels
            FROM members m
            LEFT JOIN customer_label_mapping clm ON m.member_id = clm.member_id
            LEFT JOIN customer_labels cl ON clm.label_id = cl.label_id
            LEFT JOIN orders o ON m.member_id = o.member_id
            WHERE m.role = "member"
            GROUP BY m.member_id
            ORDER BY total_spent DESC';
    
    $res = $db->query($sql);
    $customers = [];
    while ($row = $res->fetch_assoc()) {
        $row['labels'] = $row['labels'] ? explode(',', $row['labels']) : [];
        $customers[] = $row;
    }
    
    respond_json(['customers' => $customers]);
}

function get_customer_profile() {
    $db = get_db();
    $member_id = intval($_GET['member_id'] ?? 0);
    
    if (!$member_id) {
        respond_json(['error' => 'Invalid member_id'], 422);
    }
    
    // 基本信息
    $memberRes = $db->query("SELECT * FROM members WHERE member_id = $member_id");
    $member = $memberRes->fetch_assoc();
    
    // 订单统计
    $statsRes = $db->query("
        SELECT 
            COUNT(*) as total_orders,
            SUM(total_amount) as total_spent,
            AVG(total_amount) as avg_order_value,
            MAX(created_at) as last_purchase_date
        FROM orders WHERE member_id = $member_id
    ");
    $stats = $statsRes->fetch_assoc();
    
    // 购买历史
    $ordersRes = $db->query("
        SELECT order_id, total_amount, status, created_at FROM orders 
        WHERE member_id = $member_id ORDER BY created_at DESC LIMIT 10
    ");
    $orders = [];
    while ($o = $ordersRes->fetch_assoc()) {
        $orders[] = $o;
    }
    
    // 客户标签
    $labelsRes = $db->query("
        SELECT cl.label_id, cl.label_name FROM customer_labels cl
        JOIN customer_label_mapping clm ON cl.label_id = clm.label_id
        WHERE clm.member_id = $member_id
    ");
    $labels = [];
    while ($l = $labelsRes->fetch_assoc()) {
        $labels[] = $l;
    }
    
    respond_json([
        'member' => $member,
        'stats' => $stats,
        'recent_orders' => $orders,
        'labels' => $labels
    ]);
}

function add_customer_label() {
    $db = get_db();
    $member_id = intval($_POST['member_id'] ?? 0);
    $label_id = intval($_POST['label_id'] ?? 0);
    
    if (!$member_id || !$label_id) {
        respond_json(['error' => 'Invalid parameters'], 422);
    }
    
    $stmt = $db->prepare('INSERT IGNORE INTO customer_label_mapping (member_id, label_id) VALUES (?, ?)');
    $stmt->bind_param('ii', $member_id, $label_id);
    
    if ($stmt->execute()) {
        respond_json(['success' => true]);
    }
    respond_json(['error' => 'Failed'], 500);
}

function remove_customer_label() {
    $db = get_db();
    $member_id = intval($_POST['member_id'] ?? 0);
    $label_id = intval($_POST['label_id'] ?? 0);
    
    $stmt = $db->prepare('DELETE FROM customer_label_mapping WHERE member_id = ? AND label_id = ?');
    $stmt->bind_param('ii', $member_id, $label_id);
    
    if ($stmt->execute()) {
        respond_json(['success' => true]);
    }
    respond_json(['error' => 'Failed'], 500);
}

// ============ 促销管理函数 ============
function list_promotions() {
    $db = get_db();
    
    $sql = 'SELECT p.*, COUNT(pc.code_id) as code_count, SUM(pc.usage_count) as total_usage
            FROM promotions p
            LEFT JOIN promo_codes pc ON p.promotion_id = pc.promotion_id
            GROUP BY p.promotion_id
            ORDER BY p.start_date DESC';
    
    $res = $db->query($sql);
    $promotions = [];
    while ($row = $res->fetch_assoc()) {
        $promotions[] = $row;
    }
    
    respond_json(['promotions' => $promotions]);
}

function create_promotion() {
    $db = get_db();
    $title = trim($_POST['title'] ?? '');
    $type = $_POST['promotion_type'] ?? '';
    $discount_type = $_POST['discount_type'] ?? 'percent';
    $discount_value = floatval($_POST['discount_value'] ?? 0);
    $min_amount = floatval($_POST['min_amount'] ?? 0);
    $start_date = $_POST['start_date'] ?? '';
    $end_date = $_POST['end_date'] ?? '';
    
    if (!$title || !$type || !$discount_value) {
        respond_json(['error' => 'Invalid data'], 422);
    }
    
    $stmt = $db->prepare('
        INSERT INTO promotions 
        (title, promotion_type, discount_type, discount_value, min_amount, start_date, end_date, is_active, created_by)
        VALUES (?, ?, ?, ?, ?, ?, ?, 1, ?)
    ');
    
    $user_id = intval($_SESSION['user_id'] ?? 0);
    $stmt->bind_param('sssddssi', $title, $type, $discount_type, $discount_value, $min_amount, $start_date, $end_date, $user_id);
    
    if ($stmt->execute()) {
        respond_json(['success' => true, 'promotion_id' => $stmt->insert_id]);
    }
    respond_json(['error' => 'Failed'], 500);
}

function update_promotion() {
    $db = get_db();
    $promotion_id = intval($_POST['promotion_id'] ?? 0);
    $is_active = intval($_POST['is_active'] ?? 1);
    
    if (!$promotion_id) {
        respond_json(['error' => 'Invalid promotion_id'], 422);
    }
    
    $stmt = $db->prepare('UPDATE promotions SET is_active = ? WHERE promotion_id = ?');
    $stmt->bind_param('ii', $is_active, $promotion_id);
    
    if ($stmt->execute()) {
        respond_json(['success' => true]);
    }
    respond_json(['error' => 'Failed'], 500);
}

function generate_promo_code() {
    $db = get_db();
    $promotion_id = intval($_POST['promotion_id'] ?? 0);
    $code = strtoupper($_POST['code'] ?? '');
    $usage_limit = intval($_POST['usage_limit'] ?? 0);
    
    if (!$promotion_id || !$code) {
        respond_json(['error' => 'Invalid parameters'], 422);
    }
    
    $stmt = $db->prepare('INSERT INTO promo_codes (promotion_id, code, usage_limit) VALUES (?, ?, ?)');
    $stmt->bind_param('isi', $promotion_id, $code, $usage_limit);
    
    if ($stmt->execute()) {
        respond_json(['success' => true, 'code_id' => $stmt->insert_id]);
    }
    respond_json(['error' => 'Code already exists or insert failed'], 500);
}

// ============ 数据分析函数 ============
function get_sales_dashboard() {
    $db = get_db();
    
    // 今日营业额
    $todayRes = $db->query('
        SELECT 
            COUNT(*) as total_orders,
            SUM(total_amount) as revenue,
            AVG(total_amount) as avg_order_value
        FROM orders WHERE DATE(created_at) = CURDATE()
    ');
    $today = $todayRes->fetch_assoc();
    
    // 本月数据
    $monthRes = $db->query('
        SELECT 
            COUNT(*) as total_orders,
            SUM(total_amount) as revenue
        FROM orders 
        WHERE YEAR(created_at) = YEAR(CURDATE()) 
        AND MONTH(created_at) = MONTH(CURDATE())
    ');
    $month = $monthRes->fetch_assoc();
    
    // 按日期汇总（最近7天）
    $weekRes = $db->query('
        SELECT DATE(created_at) as date, COUNT(*) as orders, SUM(total_amount) as revenue
        FROM orders
        WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        GROUP BY DATE(created_at)
        ORDER BY date DESC
    ');
    $week = [];
    while ($row = $weekRes->fetch_assoc()) {
        $week[] = $row;
    }
    
    respond_json([
        'today' => $today,
        'this_month' => $month,
        'last_7_days' => $week
    ]);
}

function get_product_ranking() {
    $db = get_db();
    
    // 热卖商品（按销量）
    $sql = '
        SELECT p.product_id, p.name, p.price, p.stock,
               COUNT(oi.order_item_id) as sales_count,
               SUM(oi.quantity) as total_quantity,
               SUM(oi.quantity * oi.unit_price) as total_revenue
        FROM products p
        LEFT JOIN order_items oi ON p.product_id = oi.product_id
        GROUP BY p.product_id
        ORDER BY total_revenue DESC
        LIMIT 20
    ';
    
    $res = $db->query($sql);
    $products = [];
    while ($row = $res->fetch_assoc()) {
        $products[] = $row;
    }
    
    respond_json(['top_products' => $products]);
}

function get_conversion_funnel() {
    $db = get_db();
    
    // 获取漏斗数据
    $funnel = [
        'page_views' => 0,
        'product_views' => 0,
        'add_to_cart' => 0,
        'checkout_start' => 0,
        'purchases' => 0
    ];
    
    $events = ['page_view', 'product_view', 'add_to_cart', 'checkout_start', 'purchase'];
    foreach ($events as $evt) {
        $res = $db->query("SELECT COUNT(*) as cnt FROM analytics_events WHERE event_type = '$evt'");
        $row = $res->fetch_assoc();
        $key = str_replace('_', '_', $evt);
        if ($evt === 'page_view') $key = 'page_views';
        if ($evt === 'product_view') $key = 'product_views';
        if ($evt === 'checkout_start') $key = 'checkout_start';
        if ($evt === 'purchase') $key = 'purchases';
        $funnel[$key] = intval($row['cnt']);
    }
    
    // 计算转化率
    $conv_rates = [];
    if ($funnel['page_views'] > 0) {
        $conv_rates['product_view_rate'] = round($funnel['product_views'] / $funnel['page_views'] * 100, 2);
        $conv_rates['add_cart_rate'] = round($funnel['add_to_cart'] / $funnel['page_views'] * 100, 2);
        $conv_rates['checkout_rate'] = round($funnel['checkout_start'] / $funnel['add_to_cart'] * 100, 2);
        $conv_rates['purchase_rate'] = round($funnel['purchases'] / $funnel['checkout_start'] * 100, 2);
        $conv_rates['overall_rate'] = round($funnel['purchases'] / $funnel['page_views'] * 100, 2);
    }
    
    respond_json([
        'funnel' => $funnel,
        'conversion_rates' => $conv_rates
    ]);
}

function get_traffic_sources() {
    $db = get_db();
    
    $sql = '
        SELECT 
            source_channel,
            COUNT(*) as order_count,
            SUM(total_amount) as revenue,
            AVG(total_amount) as avg_order_value
        FROM orders
        WHERE source_channel IS NOT NULL
        GROUP BY source_channel
        ORDER BY revenue DESC
    ';
    
    $res = $db->query($sql);
    $sources = [];
    while ($row = $res->fetch_assoc()) {
        $sources[] = $row;
    }
    
    respond_json(['sources' => $sources]);
}

function get_abandoned_carts() {
    $db = get_db();
    
    $sql = '
        SELECT ac.*, m.name, m.email
        FROM abandoned_carts ac
        JOIN members m ON ac.member_id = m.member_id
        WHERE ac.is_recovered = 0
        AND ac.recovery_attempts < 3
        ORDER BY ac.abandoned_at DESC
        LIMIT 50
    ';
    
    $res = $db->query($sql);
    $carts = [];
    while ($row = $res->fetch_assoc()) {
        $row['cart_items'] = json_decode($row['cart_items'], true);
        $carts[] = $row;
    }
    
    respond_json(['abandoned_carts' => $carts]);
}

// ============ 库存管理函数 ============
function get_inventory_alerts() {
    $db = get_db();
    
    $sql = '
        SELECT ia.*, p.name, p.stock
        FROM inventory_alerts ia
        JOIN products p ON ia.product_id = p.product_id
        WHERE ia.is_resolved = 0
        ORDER BY ia.created_at DESC
    ';
    
    $res = $db->query($sql);
    $alerts = [];
    while ($row = $res->fetch_assoc()) {
        $alerts[] = $row;
    }
    
    respond_json(['alerts' => $alerts]);
}

function resolve_inventory_alert() {
    $db = get_db();
    $alert_id = intval($_POST['alert_id'] ?? 0);
    $action = $_POST['action'] ?? 'resolve'; // resolve 或 restock
    
    if (!$alert_id) {
        respond_json(['error' => 'Invalid alert_id'], 422);
    }
    
    $stmt = $db->prepare('UPDATE inventory_alerts SET is_resolved = 1, resolved_at = NOW() WHERE alert_id = ?');
    $stmt->bind_param('i', $alert_id);
    
    if ($stmt->execute()) {
        respond_json(['success' => true]);
    }
    respond_json(['error' => 'Failed'], 500);
}

// ============ 产品规格管理函数 ============
function list_product_variants() {
    $db = get_db();
    $product_id = intval($_GET['product_id'] ?? 0);
    
    $sql = 'SELECT * FROM product_variants WHERE product_id = ? ORDER BY variant_id';
    $stmt = $db->prepare($sql);
    $stmt->bind_param('i', $product_id);
    $stmt->execute();
    
    $res = $stmt->get_result();
    $variants = [];
    while ($row = $res->fetch_assoc()) {
        $variants[] = $row;
    }
    
    respond_json(['variants' => $variants]);
}

function create_product_variant() {
    $db = get_db();
    $product_id = intval($_POST['product_id'] ?? 0);
    $variant_name = trim($_POST['variant_name'] ?? '');
    $color = trim($_POST['color'] ?? '');
    $size = trim($_POST['size'] ?? '');
    $stock = intval($_POST['stock'] ?? 0);
    $price_offset = floatval($_POST['price_offset'] ?? 0);
    
    if (!$product_id || !$variant_name) {
        respond_json(['error' => 'Invalid data'], 422);
    }
    
    $sku = strtoupper($product_id . '-' . substr($color, 0, 2) . substr($size, 0, 2) . time());
    
    $stmt = $db->prepare('
        INSERT INTO product_variants 
        (product_id, variant_name, sku, color, size, stock, price_offset)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ');
    
    $stmt->bind_param('isssii', $product_id, $variant_name, $sku, $color, $size, $stock, $price_offset);
    
    if ($stmt->execute()) {
        respond_json(['success' => true, 'variant_id' => $stmt->insert_id]);
    }
    respond_json(['error' => 'Failed'], 500);
}

function update_product_variant() {
    $db = get_db();
    $variant_id = intval($_POST['variant_id'] ?? 0);
    $stock = intval($_POST['stock'] ?? 0);
    $is_active = intval($_POST['is_active'] ?? 1);
    
    if (!$variant_id) {
        respond_json(['error' => 'Invalid variant_id'], 422);
    }
    
    $stmt = $db->prepare('UPDATE product_variants SET stock = ?, is_active = ? WHERE variant_id = ?');
    $stmt->bind_param('iii', $stock, $is_active, $variant_id);
    
    if ($stmt->execute()) {
        respond_json(['success' => true]);
    }
    respond_json(['error' => 'Failed'], 500);
}

// ============ 物流管理函数 ============
function create_logistics_order() {
    $db = get_db();
    $order_id = intval($_POST['order_id'] ?? 0);
    $provider = trim($_POST['logistics_provider'] ?? '');
    $tracking = trim($_POST['tracking_number'] ?? '');
    $fee = floatval($_POST['logistics_fee'] ?? 0);
    
    if (!$order_id || !$provider) {
        respond_json(['error' => 'Invalid data'], 422);
    }
    
    $stmt = $db->prepare('
        INSERT INTO logistics_orders 
        (order_id, logistics_provider, tracking_number, logistics_fee, status)
        VALUES (?, ?, ?, ?, "created")
    ');
    
    $stmt->bind_param('issd', $order_id, $provider, $tracking, $fee);
    
    if ($stmt->execute()) {
        respond_json(['success' => true, 'logistics_id' => $stmt->insert_id]);
    }
    respond_json(['error' => 'Failed'], 500);
}

function list_logistics_orders() {
    $db = get_db();
    
    $sql = '
        SELECT lo.*, o.order_id, o.total_amount, m.name, m.email
        FROM logistics_orders lo
        JOIN orders o ON lo.order_id = o.order_id
        JOIN members m ON o.member_id = m.member_id
        ORDER BY lo.created_at DESC
        LIMIT 100
    ';
    
    $res = $db->query($sql);
    $orders = [];
    while ($row = $res->fetch_assoc()) {
        $orders[] = $row;
    }
    
    respond_json(['logistics_orders' => $orders]);
}
?>
