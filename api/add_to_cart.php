<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

$conn = new mysqli("localhost", "root", "", "vr_mall");

if ($conn->connect_error) {
    echo json_encode(["status" => "error", "msg" => "DB連線失敗: " . $conn->connect_error]);
    exit;
}

$raw = file_get_contents("php://input");
file_put_contents("debug.txt", $raw); // 保留debug

$data = json_decode($raw, true);

if (!$data || !isset($data["member_id"]) || !isset($data["items"])) {
    echo json_encode(["status" => "error", "msg" => "資料格式錯誤或為空"]);
    exit;
}

$member_id = (int)$data["member_id"];
$items = $data["items"];

foreach ($items as $item) {
    $product_id = (int)$item["product_id"];
    $quantity = (int)$item["quantity"];

    // ✅ UPSERT：有就更新數量，沒有就新增
    $stmt = $conn->prepare("
        INSERT INTO member_cart_items (member_id, product_id, quantity)
        VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE quantity = quantity + VALUES(quantity)
    ");
    $stmt->bind_param("iii", $member_id, $product_id, $quantity);

    if (!$stmt->execute()) {
        echo json_encode(["status" => "error", "msg" => $stmt->error]);
        exit;
    }
}

echo json_encode(["status" => "success", "msg" => "購物車已儲存"]);
?>