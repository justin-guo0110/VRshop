#!/usr/bin/env php
<?php
/**
 * VR Mall API 測試和驗證指令碼
 * 用於快速驗證所有 API 端點
 * 使用: php test_api.php
 */

require_once __DIR__ . '/api/db.php';
$conn = get_db();

// 顏色輸出
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

// 測試資料庫連線
print_info("=" . str_repeat("=", 50));
print_info("VR Mall 系統檢查");
print_info("=" . str_repeat("=", 50));

print_info("\n【檢查 1】資料庫連線...");
if ($conn->connect_error) {
    print_error("資料庫連線失敗: " . $conn->connect_error);
    exit(1);
} else {
    print_success("資料庫已連線");
}

// 檢查所有必要的表
print_info("\n【檢查 2】資料庫表...");
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
    print_success("所有必要的表都已存在 (" . count($required_tables) . " 個)");
}

// 檢查資料統計
print_info("\n【檢查 3】資料統計...");
$tables_count = [
    'members' => 'SELECT COUNT(*) FROM members',
    'orders' => 'SELECT COUNT(*) FROM orders',
    'products' => 'SELECT COUNT(*) FROM products',
    'promotions' => 'SELECT COUNT(*) FROM promotions',
    'customers_labels' => 'SELECT COUNT(*) FROM customer_label_mapping',
    'analytics_events' => 'SELECT COUNT(*) FROM analytics_events',
    'abandoned_carts' => 'SELECT COUNT(*) FROM abandoned_carts'
];

echo "\n┌─ 資料庫統計 ────────────────────────┐\n";
foreach ($tables_count as $name => $query) {
    $result = $conn->query($query);
    $count = $result->fetch_row()[0];
    printf("│ %-30s %6d │\n", $name . ":", $count);
}
echo "└──────────────────────────────────────┘\n";

// 檢查管理員帳戶
print_info("\n【檢查 4】管理員帳戶...");
$admin_result = $conn->query("SELECT COUNT(*) FROM members WHERE role = 'admin'");
$admin_count = $admin_result->fetch_row()[0];

if ($admin_count > 0) {
    print_success("找到 $admin_count 個管理員帳戶");
    $admins = $conn->query("SELECT email, name FROM members WHERE role = 'admin' LIMIT 5");
    while ($admin = $admins->fetch_assoc()) {
        print_info("  • " . $admin['email'] . " (" . $admin['name'] . ")");
    }
} else {
    print_warning("未找到管理員帳戶，請前往 admin_setup.php 建立");
}

// 檢查 API 端點
print_info("\n【檢查 5】API 端點可存取性...");
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

print_info("  (需要在瀏覽器中登入後測試)");
echo "\n┌─ API 端點列表 ────────────────────────┐\n";
foreach ($api_endpoints as $action => $method) {
    $url = "http://localhost/VR%20shop/api/operations.php?action=" . urlencode($action);
    printf("│ %-40s │\n", "[$method] $action");
}
echo "└────────────────────────────────────────┘\n";

// 快速驗證
print_info("\n【檢查 6】快速驗證...");

// 驗證客戶標籤
$labels = $conn->query("SELECT * FROM customer_labels LIMIT 3");
$label_count = $labels->num_rows;
print_info("  • 客戶標籤: $label_count 條");

// 驗證促銷活動
$promos = $conn->query("SELECT * FROM promotions WHERE is_active = 1");
$active_promos = $promos->num_rows;
print_info("  • 活躍促銷: $active_promos 個");

// 驗證實時資料
$today_orders = $conn->query("SELECT COUNT(*) FROM orders WHERE DATE(created_at) = CURDATE()");
$today_count = $today_orders->fetch_row()[0];
print_info("  • 今日訂單: $today_count 筆");

// 系統總結
print_info("\n【系統總結】");
echo "\n✅ 系統檢查完成！\n\n";

print_info("後續步驟:");
print_info("  1. 前往管理員配置: http://localhost/VR%20shop/admin_setup.php");
print_info("  2. 建立或登入管理員帳戶");
print_info("  3. 前往營運後台: http://localhost/VR%20shop/views/operations.php");
print_info("  4. 檢視各個功能模組");

print_info("\n快速連結:");
echo "  📘 功能文件: " . Colors::BLUE . "ECOMMERCE_BACKEND_GUIDE.md" . Colors::RESET . "\n";
echo "  📗 整合指南: " . Colors::BLUE . "API_INTEGRATION_GUIDE.md" . Colors::RESET . "\n";
echo "  📙 部署清單: " . Colors::BLUE . "DEPLOYMENT_CHECKLIST.md" . Colors::RESET . "\n";
echo "  📕 快速開始: " . Colors::BLUE . "QUICK_START_GUIDE.md" . Colors::RESET . "\n";

print_info("\n祝您使用愉快！🎉\n");
?>
