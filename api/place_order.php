<?php
$conn = new mysqli("localhost", "root", "", "vr_mall");
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/db.php';

$data = json_decode(file_get_contents("php://input"), true);

if (!$data) {
    die(json_encode([
        "success" => false,
        "message" => "JSON解析失敗"
    ]));
}

$member_id = $data["member_id"] ?? 0;
$total_amount = $data["total_amount"] ?? 0;
$items = $data["items"] ?? [];

if ($member_id == 0 || empty($items)) {
    die(json_encode([
        "success" => false,
        "message" => "資料不足"
    ]));
}

$conn->begin_transaction();

try {

    // =========================
    // 取得會員地址
    // =========================

    $stmt = $conn->prepare("
        SELECT
            address_id,
            recipient_name,
            phone,
            address_line
        FROM member_addresses
        WHERE member_id = ?
        ORDER BY address_id ASC
        LIMIT 1
    ");

    $stmt->bind_param("i", $member_id);
    $stmt->execute();

    $result = $stmt->get_result();

    if ($result->num_rows == 0) {
        throw new Exception("找不到會員地址");
    }

    $address = $result->fetch_assoc();

    // =========================
    // 新增 orders
    // =========================

    $stmt = $conn->prepare("
        INSERT INTO orders (
            member_id,
            address_id,
            ship_name,
            ship_phone,
            ship_address_line,
            total_amount,
            discount_amount,
            shipping_fee,
            shipping_method,
            payment_method
        )
        VALUES (?, ?, ?, ?, ?, ?, 0, 100, '宅配', '信用卡')
    ");

    $stmt->bind_param(
        "iisssi",
        $member_id,
        $address["address_id"],
        $address["recipient_name"],
        $address["phone"],
        $address["address_line"],
        $total_amount
    );

    $stmt->execute();

    $order_id = $conn->insert_id;

    // =========================
    // 新增 order_items
    // =========================

    foreach ($items as $item)
    {
        $product_id = $item["product_id"];
        $quantity = $item["quantity"];

        $stmt = $conn->prepare("
            SELECT price
            FROM products
            WHERE product_id = ?
        ");

        $stmt->bind_param("i", $product_id);
        $stmt->execute();

        $productResult = $stmt->get_result();

        if ($productResult->num_rows == 0) {
            throw new Exception("商品不存在");
        }

        $product = $productResult->fetch_assoc();

        $price = $product["price"];

        $stmt = $conn->prepare("
            INSERT INTO order_items (
                order_id,
                product_id,
                quantity,
                unit_price
            )
            VALUES (?, ?, ?, ?)
        ");

        $stmt->bind_param(
            "iiid",
            $order_id,
            $product_id,
            $quantity,
            $price
        );

        $stmt->execute();
    }

    $conn->commit();

    echo json_encode([
        "success" => true,
        "order_id" => $order_id
    ]);

}
catch (Exception $e)
{
    $conn->rollback();

    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);
}