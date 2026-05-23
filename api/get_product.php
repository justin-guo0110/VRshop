<?php
header('Content-Type: application/json; charset=utf-8');

$host = 'localhost';
$user = 'root';
$pass = '';
$db   = 'vr_mall';

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {

    echo json_encode([
        'success' => false,
        'message' => '資料庫連線失敗'
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

$conn->set_charset('utf8mb4');

$product_id =
    isset($_GET['product_id'])
    ? intval($_GET['product_id'])
    : 0;

if ($product_id <= 0) {

    echo json_encode([
        'success' => false,
        'message' => 'product_id 不正確'
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

$sql = "
SELECT
product_id,
name,
price,
stock
FROM products
WHERE product_id = ?
";

$stmt = $conn->prepare($sql);

$stmt->bind_param("i", $product_id);

$stmt->execute();

$result = $stmt->get_result();

$product = $result->fetch_assoc();

if ($product) {

    echo json_encode([
        'success' => true,
        'data' => [

            'product_id' =>
                (int)$product['product_id'],

            'name' =>
                $product['name'],

            'price' =>
                (float)$product['price'],

            'stock' =>
                (int)$product['stock']
        ]
    ], JSON_UNESCAPED_UNICODE);

} else {

    echo json_encode([
        'success' => false,
        'message' => '找不到商品'
    ], JSON_UNESCAPED_UNICODE);
}

$stmt->close();
$conn->close();
?>