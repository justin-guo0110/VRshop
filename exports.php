<?php
/**
 * VR Mall - 数据导出工具
 * 支持导出为 CSV、JSON、Excel 格式
 */

require_once __DIR__ . '/api/db.php';
$conn = get_db();

// 检查权限
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized']);
    exit(1);
}

// 获取导出参数
$table = $_GET['table'] ?? 'orders';
$format = $_GET['format'] ?? 'csv'; // csv, json, excel
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

// 允许导出的表
$allowed_tables = [
    'orders', 'order_items', 'products', 'members',
    'promotions', 'promo_codes', 'analytics_events',
    'abandoned_carts', 'payment_transactions', 'logistics_orders'
];

if (!in_array($table, $allowed_tables)) {
    exit('Invalid table');
}

// 构建查询
$query = "SELECT * FROM `$table`";

// 添加日期过滤
if ($table === 'orders' && $date_from && $date_to) {
    $query .= " WHERE created_at BETWEEN '" . $conn->real_escape_string($date_from) . "' AND '" . $conn->real_escape_string($date_to) . "'";
}

$query .= " ORDER BY created_at DESC LIMIT 10000";

$result = $conn->query($query);

if (!$result) {
    exit('Query error: ' . $conn->error);
}

// 获取数据
$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

// 根据格式导出
if ($format === 'json') {
    header('Content-Type: application/json; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $table . '_' . date('Y-m-d_H-i-s') . '.json"');
    echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    
} elseif ($format === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $table . '_' . date('Y-m-d_H-i-s') . '.csv"');
    
    // 输出 BOM 使 Excel 正确识别 UTF-8
    echo "\xEF\xBB\xBF";
    
    // 输出列名
    if (!empty($data)) {
        fputcsv(STDOUT, array_keys($data[0]));
        
        // 输出数据
        foreach ($data as $row) {
            fputcsv(STDOUT, array_values($row));
        }
    }
    
} elseif ($format === 'excel') {
    // 简单的 Excel 格式 (制表符分隔)
    header('Content-Type: application/vnd.ms-excel; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $table . '_' . date('Y-m-d_H-i-s') . '.xls"');
    
    echo "\xEF\xBB\xBF";
    
    if (!empty($data)) {
        // 列名
        echo implode("\t", array_keys($data[0])) . "\r\n";
        
        // 数据
        foreach ($data as $row) {
            echo implode("\t", array_values($row)) . "\r\n";
        }
    }
}
?>
