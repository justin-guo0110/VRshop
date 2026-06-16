<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/notification_helpers.php';

$action = $_GET['action'] ?? '';
$user = require_login();

switch ($action) {
    case 'get_profile':
        get_profile($user);
        break;
    case 'update_profile':
        update_profile($user);
        break;
    case 'list_addresses':
        list_addresses($user);
        break;
    case 'create_address':
        create_address($user);
        break;
    case 'update_address':
        update_address($user);
        break;
    case 'delete_address':
        delete_address($user);
        break;
    case 'list_cards':
        list_cards($user);
        break;

    case 'create_card':
        create_card($user);
        break;

    case 'delete_card':
        delete_card($user);
        break;

    case 'set_default_card':
        set_default_card($user);
        break;
    case 'list_notifications':
        list_notifications($user);
        break;
    case 'mark_notification_read':
        mark_notification_read($user);
        break;
    default:
        respond_json(['error' => 'Unknown action'], 400);
}

function get_profile(array $user): void
{
    respond_json([
        'profile' => [
            'member_id' => $user['member_id'],
            'email' => $user['email'],
            'name' => $user['name'],
            'phone' => $user['phone'],
            'role' => $user['role']
        ]
    ]);
}

function update_profile(array $user): void
{
    $db = get_db();
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    if ($name === '') {
        respond_json(['error' => 'Name required'], 422);
    }
    $stmt = $db->prepare('UPDATE members SET name = ?, phone = ? WHERE member_id = ?');
    $stmt->bind_param('ssi', $name, $phone, $user['member_id']);
    if ($stmt->execute()) {
        $_SESSION['user']['name'] = $name;
        $_SESSION['user']['phone'] = $phone;
        respond_json(['success' => true]);
    }
    respond_json(['error' => 'Update failed'], 500);
}

function list_addresses(array $user): void
{
    $db = get_db();
    $stmt = $db->prepare('SELECT address_id, recipient_name, phone, address_line, is_default FROM member_addresses WHERE member_id = ? ORDER BY is_default DESC, address_id DESC');
    $stmt->bind_param('i', $user['member_id']);
    $stmt->execute();
    $res = $stmt->get_result();
    $data = $res->fetch_all(MYSQLI_ASSOC);
    respond_json(['addresses' => $data]);
}

function create_address(array $user): void
{
    $db = get_db();
    $recipient = trim($_POST['recipient_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address_line'] ?? '');
    $is_default = intval($_POST['is_default'] ?? 0) ? 1 : 0;
    if ($recipient === '' || $address === '') {
        respond_json(['error' => 'Recipient and address required'], 422);
    }
    if ($is_default) {
        $db->query('UPDATE member_addresses SET is_default = 0 WHERE member_id = ' . intval($user['member_id']));
    }
    $stmt = $db->prepare('INSERT INTO member_addresses (member_id, recipient_name, phone, address_line, is_default) VALUES (?, ?, ?, ?, ?)');
    $stmt->bind_param('isssi', $user['member_id'], $recipient, $phone, $address, $is_default);
    if ($stmt->execute()) {
        respond_json(['success' => true, 'address_id' => $stmt->insert_id]);
    }
    respond_json(['error' => 'Create failed'], 500);
}

function update_address(array $user): void
{
    $db = get_db();
    $address_id = intval($_POST['address_id'] ?? 0);
    $recipient = trim($_POST['recipient_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address_line'] ?? '');
    $is_default = intval($_POST['is_default'] ?? 0) ? 1 : 0;
    if (!$address_id || $recipient === '' || $address === '') {
        respond_json(['error' => 'Invalid data'], 422);
    }
    if ($is_default) {
        $db->query('UPDATE member_addresses SET is_default = 0 WHERE member_id = ' . intval($user['member_id']));
    }
    $stmt = $db->prepare('UPDATE member_addresses SET recipient_name = ?, phone = ?, address_line = ?, is_default = ? WHERE address_id = ? AND member_id = ?');
    $stmt->bind_param('sssiii', $recipient, $phone, $address, $is_default, $address_id, $user['member_id']);
    if ($stmt->execute()) {
        respond_json(['success' => true]);
    }
    respond_json(['error' => 'Update failed'], 500);
}

function delete_address(array $user): void
{
    $db = get_db();
    $address_id = intval($_POST['address_id'] ?? 0);
    if (!$address_id) {
        respond_json(['error' => 'Address id required'], 422);
    }
    $stmt = $db->prepare('DELETE FROM member_addresses WHERE address_id = ? AND member_id = ?');
    $stmt->bind_param('ii', $address_id, $user['member_id']);
    if ($stmt->execute()) {
        respond_json(['success' => true]);
    }
    respond_json(['error' => 'Delete failed'], 500);
}

function list_notifications(array $user): void
{
    $db = get_db();
    $notifications = list_member_notifications($db, intval($user['member_id']));
    respond_json(['notifications' => $notifications]);
}

function mark_notification_read(array $user): void
{
    $notification_id = intval($_POST['notification_id'] ?? 0);
    if ($notification_id <= 0) {
        respond_json(['error' => 'Notification id required'], 422);
    }
    $db = get_db();
    $updated = mark_member_notification_read($db, intval($user['member_id']), $notification_id);
    if ($updated) {
        respond_json(['success' => true]);
    }
    respond_json(['error' => 'Update failed or notification not found'], 500);
}

function list_cards(array $user): void
{
    $db = get_db();

    $stmt = $db->prepare(
        'SELECT card_id,
                card_holder,
                card_brand,
                card_last4,
                expiry,
                is_default
         FROM member_cards
         WHERE member_id = ?
         ORDER BY is_default DESC, card_id DESC'
    );

    if (!$stmt) {
        respond_json(['error' => 'Load cards failed: ' . $db->error], 500);
    }

    $stmt->bind_param('i', $user['member_id']);
    $stmt->execute();

    $cards = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    respond_json([
        'cards' => $cards
    ]);
}

function create_card(array $user): void
{
    $db = get_db();

    $holder = trim($_POST['card_holder'] ?? '');
    $number = preg_replace('/\D/', '', $_POST['card_number'] ?? '');
    $expiry = trim($_POST['expiry'] ?? '');
    if (!preg_match('/^(\d{2})\/(\d{2})$/', $expiry, $matches)) {
        respond_json(['error' => '請填寫正確的有效期限格式 MM/YY'], 422);
    }

    $month = intval($matches[1]);
    $year = intval($matches[2]) + 2000;

    if ($month < 1 || $month > 12) {
        respond_json(['error' => '請填寫正確的月份'], 422);
    }

    $now = new DateTime();
    $expiryDate = new DateTime($year . '-' . $month . '-01');
    $expiryDate->modify('last day of this month')->setTime(23, 59, 59);

    if ($expiryDate < $now) {
        respond_json(['error' => '信用卡已過期'], 422);
    }

    $maxYear = intval(date('Y')) + 10;

    if ($year > $maxYear) {
        respond_json(['error' => '請填寫正確日期'], 422);
    }

    $is_default = intval($_POST['is_default'] ?? 0) ? 1 : 0;

    if (
        $holder === '' ||
        $number === '' ||
        $expiry === ''
    ) {
        respond_json([
            'error' => '資料不完整'
        ], 422);
    }

    $last4 = substr($number, -4);

    $brand = 'UNKNOWN';

    if (preg_match('/^4/', $number)) {
        $brand = 'VISA';
    } elseif (preg_match('/^5[1-5]/', $number)) {
        $brand = 'MASTERCARD';
    } elseif (preg_match('/^(34|37)/', $number)) {
        $brand = 'AMEX';
    } elseif (preg_match('/^35/', $number)) {
        $brand = 'JCB';
    }

    $payment_token =
        'pm_demo_' .
        bin2hex(random_bytes(10));

    if ($is_default) {
        $db->query(
            'UPDATE member_cards
             SET is_default = 0
             WHERE member_id = ' .
            intval($user['member_id'])
        );
    }

    $stmt = $db->prepare(
        'INSERT INTO member_cards
        (
            member_id,
            card_holder,
            card_brand,
            card_last4,
            expiry,
            payment_token,
            is_default
        )
        VALUES (?, ?, ?, ?, ?, ?, ?)'
    );

    if (!$stmt) {
        respond_json(['error' => 'Create card failed: ' . $db->error], 500);
    }

    $stmt->bind_param(
        'isssssi',
        $user['member_id'],
        $holder,
        $brand,
        $last4,
        $expiry,
        $payment_token,
        $is_default
    );

    if ($stmt->execute()) {
        respond_json([
            'success' => true,
            'card_id' => $stmt->insert_id
        ]);
    }

    respond_json([
        'error' => '新增失敗: ' . $stmt->error
    ], 500);
}

function delete_card(array $user): void
{
    $db = get_db();

    $card_id = intval(
        $_POST['card_id'] ?? 0
    );

    $stmt = $db->prepare(
        'DELETE FROM member_cards
         WHERE card_id = ?
         AND member_id = ?'
    );

    $stmt->bind_param(
        'ii',
        $card_id,
        $user['member_id']
    );

    if ($stmt->execute()) {
        respond_json([
            'success' => true
        ]);
    }

    respond_json([
        'error' => '刪除失敗'
    ], 500);
}

function set_default_card(array $user): void
{
    $db = get_db();

    $card_id = intval(
        $_POST['card_id'] ?? 0
    );

    $db->query(
        'UPDATE member_cards
         SET is_default = 0
         WHERE member_id = ' .
        intval($user['member_id'])
    );

    $stmt = $db->prepare(
        'UPDATE member_cards
         SET is_default = 1
         WHERE card_id = ?
         AND member_id = ?'
    );

    $stmt->bind_param(
        'ii',
        $card_id,
        $user['member_id']
    );

    if ($stmt->execute()) {
        respond_json([
            'success' => true
        ]);
    }

    respond_json([
        'error' => '設定失敗'
    ], 500);
}
