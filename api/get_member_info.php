<?php
// 必須第一行，攔截所有雜訊輸出
ob_start();

require_once 'db.php';

header('Content-Type: application/json');

$member_id = intval($_GET['member_id'] ?? 0);

if ($member_id <= 0)
{
    ob_clean();
    echo json_encode([
        "success" => false,
        "message" => "invalid member id"
    ]);
    exit;
}

$conn = get_db();

// 先確認資料庫連線成功
if (!$conn)
{
    ob_clean();
    echo json_encode([
        "success" => false,
        "message" => "db connection failed"
    ]);
    exit;
}

$response = [
    "success" => true,
    "cards" => [],
    "addresses" => []
];

// ---------- Cards ----------
$sql = "
    SELECT card_holder, card_last4, expiry
    FROM member_cards
    WHERE member_id = ?
    ORDER BY card_id ASC
    LIMIT 3
";

$stmt = $conn->prepare($sql);

if (!$stmt)
{
    ob_clean();
    echo json_encode([
        "success" => false,
        "message" => "cards prepare failed: " . $conn->error
    ]);
    exit;
}

$stmt->bind_param("i", $member_id);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc())
{
    $response["cards"][] = $row;
}

$stmt->close();

// ---------- Addresses ----------
$sql = "
    SELECT address_id, recipient_name, phone, address_line
    FROM member_addresses
    WHERE member_id = ?
    ORDER BY address_id ASC
    LIMIT 3
";

$stmt = $conn->prepare($sql);

if (!$stmt)
{
    ob_clean();
    echo json_encode([
        "success" => false,
        "message" => "addresses prepare failed: " . $conn->error
    ]);
    exit;
}

$stmt->bind_param("i", $member_id);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc())
{
    $response["addresses"][] = $row;
}

$stmt->close();

// 清除所有雜訊，只輸出 JSON
ob_clean();
echo json_encode($response);