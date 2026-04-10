<?php
/**
 * VR Mall - 資料匯出工具
 * 支援匯出為 CSV、JSON、Excel 格式
 */

require_once __DIR__ . '/api/db.php';
$conn = get_db();

// 檢查權限
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized']);
    exit(1);
}

// 獲取參數
$table = $_GET['table'] ?? 'orders';
$format = $_GET['format'] ?? 'csv'; // csv, json, excel
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

// 允許匯出
$allowed_tables = [
    'orders', 'order_items', 'products', 'members',
    'promotions', 'promo_codes', 'analytics_events',
    'abandoned_carts', 'payment_transactions', 'logistics_orders'
];

if (!in_array($table, $allowed_tables)) {
    exit('Invalid table');
}

// 構建查詢
$query = "SELECT * FROM `$table`";

// 新增日期篩選（僅對 orders 表有效）
if ($table === 'orders' && $date_from && $date_to) {
    $query .= " WHERE created_at BETWEEN '" . $conn->real_escape_string($date_from) . "' AND '" . $conn->real_escape_string($date_to) . "'";
}

$query .= " ORDER BY created_at DESC LIMIT 10000";

$result = $conn->query($query);

if (!$result) {
    exit('Query error: ' . $conn->error);
}

// 將資料讀取到陣列中
$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

// 根據格式輸出資料
if ($format === 'json') {
    header('Content-Type: application/json; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $table . '_' . date('Y-m-d_H-i-s') . '.json"');
    echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    
} elseif ($format === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $table . '_' . date('Y-m-d_H-i-s') . '.csv"');
    
    // 輸出 UTF-8 BOM 以確保 Excel 正確識別編碼
    echo "\xEF\xBB\xBF";
    
    // 輸出列名
    if (!empty($data)) {
        fputcsv(STDOUT, array_keys($data[0]));
        
        // 輸出資料
        foreach ($data as $row) {
            fputcsv(STDOUT, array_values($row));
        }
    }
    
} elseif ($format === 'excel') {
    // 簡單的 Excel 格式（實際上是 TSV），Excel 可以直接開啟
    header('Content-Type: application/vnd.ms-excel; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $table . '_' . date('Y-m-d_H-i-s') . '.xls"');
    
    echo "\xEF\xBB\xBF";
    
    if (!empty($data)) {
        // 列名
        echo implode("\t", array_keys($data[0])) . "\r\n";
        
        // 資料
        foreach ($data as $row) {
            echo implode("\t", array_values($row)) . "\r\n";
        }
    }
}
?>
