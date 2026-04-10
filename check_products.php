<?php
require_once 'api/db.php';

$db = get_db();

// 檢查商品數量
$result = $db->query('SELECT COUNT(*) as count FROM products');
$row = $result->fetch_assoc();
echo "商品總數: " . $row['count'] . "\n\n";

// 列出所有商品
echo "所有商品列表:\n";
$result = $db->query('SELECT product_id, name, price, stock FROM products ORDER BY product_id LIMIT 10');
while ($row = $result->fetch_assoc()) {
    echo "ID: {$row['product_id']}, Name: {$row['name']}, Price: {$row['price']}, Stock: {$row['stock']}\n";
}
?>
