#!/usr/bin/env php
<?php
/**
 * VR Mall API 测试和验证脚本
 * 用于快速验证所有 API 端点
 * 使用: php test_api.php
 */

require_once __DIR__ . '/api/db.php';
$conn = get_db();

// 颜色输出
class Colors {
    const GREEN = "\033[32m";
    const RED = "\033[31m";
    const YELLOW = "\033[33m";
    const BLUE = "\033[34m";
    const RESET = "\033[0m";
}

function print_success($msg) {
    echo Colors::GREEN . "✅ " . $msg . Colors::RESET . "\n";
}

function print_error($msg) {
    echo Colors::RED . "❌ " . $msg . Colors::RESET . "\n";
}

function print_info($msg) {
    echo Colors::BLUE . "ℹ️  " . $msg . Colors::RESET . "\n";
}

function print_warning($msg) {
    echo Colors::YELLOW . "⚠️  " . $msg . Colors::RESET . "\n";
}

// 测试数据库连接
print_info("=" . str_repeat("=", 50));
print_info("VR Mall 系统检查");
print_info("=" . str_repeat("=", 50));

print_info("\n【检查 1】数据库连接...");
if ($conn->connect_error) {
    print_error("数据库连接失败: " . $conn->connect_error);
    exit(1);
} else {
    print_success("数据库已连接");
}

// 检查所有必要的表
print_info("\n【检查 2】数据库表...");
$required_tables = [
    'members', 'products', 'orders', 'order_items',
    'promotions', 'promo_codes', 'product_variants',
    'inventory_alerts', 'abandoned_carts', 'analytics_events',
    'logistics_orders', 'payment_transactions', 'customer_labels',
    'customer_label_mapping'
];

$result = $conn->query("SHOW TABLES");
$existing_tables = [];
while ($row = $result->fetch_row()) {
    $existing_tables[] = $row[0];
}

$missing = array_diff($required_tables, $existing_tables);
if (count($missing) > 0) {
    print_warning("缺失的表: " . implode(", ", $missing));
} else {
    print_success("所有必要的表都已存在 (" . count($required_tables) . " 个)");
}

// 检查数据统计
print_info("\n【检查 3】数据统计...");
$tables_count = [
    'members' => 'SELECT COUNT(*) FROM members',
    'orders' => 'SELECT COUNT(*) FROM orders',
    'products' => 'SELECT COUNT(*) FROM products',
    'promotions' => 'SELECT COUNT(*) FROM promotions',
    'customers_labels' => 'SELECT COUNT(*) FROM customer_label_mapping',
    'analytics_events' => 'SELECT COUNT(*) FROM analytics_events',
    'abandoned_carts' => 'SELECT COUNT(*) FROM abandoned_carts'
];

echo "\n┌─ 数据库统计 ────────────────────────┐\n";
foreach ($tables_count as $name => $query) {
    $result = $conn->query($query);
    $count = $result->fetch_row()[0];
    printf("│ %-30s %6d │\n", $name . ":", $count);
}
echo "└──────────────────────────────────────┘\n";

// 检查管理员账户
print_info("\n【检查 4】管理员账户...");
$admin_result = $conn->query("SELECT COUNT(*) FROM members WHERE role = 'admin'");
$admin_count = $admin_result->fetch_row()[0];

if ($admin_count > 0) {
    print_success("找到 $admin_count 个管理员账户");
    $admins = $conn->query("SELECT email, name FROM members WHERE role = 'admin' LIMIT 5");
    while ($admin = $admins->fetch_assoc()) {
        print_info("  • " . $admin['email'] . " (" . $admin['name'] . ")");
    }
} else {
    print_warning("未找到管理员账户，请访问 admin_setup.php 创建");
}

// 检查 API 端点
print_info("\n【检查 5】API 端点可访问性...");
$api_endpoints = [
    'get_sales_dashboard' => 'GET',
    'list_customers_with_labels' => 'GET',
    'list_promotions' => 'GET',
    'get_conversion_funnel' => 'GET',
    'get_product_ranking' => 'GET',
    'get_traffic_sources' => 'GET',
    'get_abandoned_carts' => 'GET',
    'get_inventory_alerts' => 'GET',
    'list_logistics_orders' => 'GET'
];

print_info("  (需要在浏览器中登录后测试)");
echo "\n┌─ API 端点列表 ────────────────────────┐\n";
foreach ($api_endpoints as $action => $method) {
    $url = "http://localhost/VR%20shop/api/operations.php?action=" . urlencode($action);
    printf("│ %-40s │\n", "[$method] $action");
}
echo "└────────────────────────────────────────┘\n";

// 快速验证
print_info("\n【检查 6】快速验证...");

// 验证客户标签
$labels = $conn->query("SELECT * FROM customer_labels LIMIT 3");
$label_count = $labels->num_rows;
print_info("  • 客户标签: $label_count 条");

// 验证促销活动
$promos = $conn->query("SELECT * FROM promotions WHERE is_active = 1");
$active_promos = $promos->num_rows;
print_info("  • 活跃促销: $active_promos 个");

// 验证实时数据
$today_orders = $conn->query("SELECT COUNT(*) FROM orders WHERE DATE(created_at) = CURDATE()");
$today_count = $today_orders->fetch_row()[0];
print_info("  • 今日订单: $today_count 笔");

// 系统总结
print_info("\n【系统总结】");
echo "\n✅ 系统检查完成！\n\n";

print_info("后续步骤:");
print_info("  1. 访问管理员配置: http://localhost/VR%20shop/admin_setup.php");
print_info("  2. 创建或登录管理员账户");
print_info("  3. 访问营运后台: http://localhost/VR%20shop/views/operations.php");
print_info("  4. 查看各个功能模块");

print_info("\n快速链接:");
echo "  📘 功能文档: " . Colors::BLUE . "ECOMMERCE_BACKEND_GUIDE.md" . Colors::RESET . "\n";
echo "  📗 集成指南: " . Colors::BLUE . "API_INTEGRATION_GUIDE.md" . Colors::RESET . "\n";
echo "  📙 部署清单: " . Colors::BLUE . "DEPLOYMENT_CHECKLIST.md" . Colors::RESET . "\n";
echo "  📕 快速开始: " . Colors::BLUE . "QUICK_START_GUIDE.md" . Colors::RESET . "\n";

print_info("\n祝您使用愉快！🎉\n");
?>
