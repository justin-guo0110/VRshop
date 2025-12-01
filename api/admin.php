<?php
require_once __DIR__ . '/db.php';
$user = require_admin();

$action = $_GET['action'] ?? '';
switch ($action) {
    case 'list_orders':
        list_orders();
        break;
    case 'update_order_status':
        update_order_status();
        break;
    case 'list_products':
        list_products();
        break;
    case 'update_product_status':
        update_product_status();
        break;
    case 'update_product':
        update_product();
        break;
    case 'get_all_chats':
        get_all_chats();
        break;
    case 'get_chat_history':
        get_chat_history();
        break;
    case 'reply_chat':
        reply_chat();
        break;
    default:
        respond_json(['error' => 'Unknown action'], 400);
}

function list_orders(): void {
    $db = get_db();
    $sql = 'SELECT o.order_id, o.member_id, o.address_id, o.total_amount, o.status, o.created_at, m.email, m.name
            FROM orders o
            JOIN members m ON o.member_id = m.member_id
            ORDER BY o.created_at DESC';
    $res = $db->query($sql);
    $orders = [];
    while ($row = $res->fetch_assoc()) {
        $row['items'] = [];
        $orders[$row['order_id']] = $row;
    }
    if (!empty($orders)) {
        $ids = implode(',', array_map('intval', array_keys($orders)));
        $itemsRes = $db->query("SELECT oi.order_id, oi.order_item_id, oi.product_id, oi.quantity, oi.unit_price, p.name FROM order_items oi JOIN products p ON oi.product_id = p.product_id WHERE oi.order_id IN ($ids)");
        while ($item = $itemsRes->fetch_assoc()) {
            $orders[$item['order_id']]['items'][] = $item;
        }
    }
    respond_json(['orders' => array_values($orders)]);
}

function update_order_status(): void {
    $db = get_db();
    $order_id = intval($_POST['order_id'] ?? 0);
    $status = $_POST['status'] ?? '';
    $allowed = ['pending', 'preparing', 'shipping', 'done'];
    if (!$order_id || !in_array($status, $allowed, true)) {
        respond_json(['error' => 'Invalid data'], 422);
    }
    $stmt = $db->prepare('UPDATE orders SET status = ? WHERE order_id = ?');
    $stmt->bind_param('si', $status, $order_id);
    if ($stmt->execute()) {
        respond_json(['success' => true]);
    }
    respond_json(['error' => 'Update failed'], 500);
}

function list_products(): void {
    $db = get_db();
    $res = $db->query('SELECT product_id, name, category, description, price, stock, image_url, is_active FROM products ORDER BY product_id DESC');
    $products = $res->fetch_all(MYSQLI_ASSOC);
    respond_json(['products' => $products]);
}

function update_product_status(): void {
    $db = get_db();
    $product_id = intval($_POST['product_id'] ?? 0);
    $is_active = intval($_POST['is_active'] ?? 0) ? 1 : 0;
    if (!$product_id) {
        respond_json(['error' => 'Product id required'], 422);
    }
    $stmt = $db->prepare('UPDATE products SET is_active = ? WHERE product_id = ?');
    $stmt->bind_param('ii', $is_active, $product_id);
    if ($stmt->execute()) {
        respond_json(['success' => true]);
    }
    respond_json(['error' => 'Update failed'], 500);
}

function update_product(): void {
    $db = get_db();
    $product_id = intval($_POST['product_id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = floatval($_POST['price'] ?? 0);
    $stock = intval($_POST['stock'] ?? 0);
    $image_url = trim($_POST['image_url'] ?? '');
    if (!$product_id || $name === '') {
        respond_json(['error' => 'Invalid data'], 422);
    }
    $stmt = $db->prepare('UPDATE products SET name = ?, category = ?, description = ?, price = ?, stock = ?, image_url = ? WHERE product_id = ?');
    $stmt->bind_param('sssdisi', $name, $category, $description, $price, $stock, $image_url, $product_id);
    if ($stmt->execute()) {
        respond_json(['success' => true]);
    }
    respond_json(['error' => 'Update failed'], 500);
}

function get_all_chats(): void {
    $db = get_db();
    
    // Group by user_id or session_id to get unique conversations
    // We want the last message time and content for the list
    $sql = "
        SELECT 
            COALESCE(user_id, session_id) as chat_id,
            MAX(created_at) as last_activity,
            (SELECT message FROM chat_messages m2 WHERE (m2.user_id = m1.user_id OR m2.session_id = m1.session_id) ORDER BY created_at DESC LIMIT 1) as last_message
        FROM chat_messages m1
        GROUP BY COALESCE(user_id, session_id)
        ORDER BY last_activity DESC
    ";
    
    $res = $db->query($sql);
    if ($res) {
        $chats = $res->fetch_all(MYSQLI_ASSOC);
        respond_json(['success' => true, 'chats' => $chats]);
    } else {
        respond_json(['error' => 'Db error'], 500);
    }
}

function get_chat_history(): void {
    $db = get_db();
    $chatId = $_GET['chat_id'] ?? '';
    
    if (!$chatId) {
        respond_json(['error' => 'Missing chat_id'], 400);
    }
    
    $isUser = is_numeric($chatId);
    
    if ($isUser) {
        $stmt = $db->prepare("SELECT * FROM chat_messages WHERE user_id = ? ORDER BY created_at ASC");
        $stmt->bind_param("i", $chatId);
    } else {
        $stmt = $db->prepare("SELECT * FROM chat_messages WHERE session_id = ? ORDER BY created_at ASC");
        $stmt->bind_param("s", $chatId);
    }
    
    $stmt->execute();
    $messages = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    respond_json(['success' => true, 'messages' => $messages]);
}

function reply_chat(): void {
    $db = get_db();

    // 後台使用 api.post(...)，傳的是 x-www-form-urlencoded，所以用 $_POST 讀取
    $message = trim($_POST['message'] ?? '');
    $userId = $_POST['user_id'] ?? null;
    $sessionId = $_POST['session_id'] ?? null;

    if ($userId !== null && $userId !== '') {
        $userId = (int)$userId;
    } else {
        $userId = null;
    }

    if ($sessionId !== null && $sessionId !== '') {
        $sessionId = (string)$sessionId;
    } else {
        $sessionId = null;
    }

    if ($message === '' || ($userId === null && $sessionId === null)) {
        respond_json(['error' => 'Invalid data'], 400);
    }

    if ($userId !== null) {
        $stmt = $db->prepare("INSERT INTO chat_messages (user_id, sender, message) VALUES (?, 'admin', ?)");
        $stmt->bind_param("is", $userId, $message);
    } else {
        $stmt = $db->prepare("INSERT INTO chat_messages (session_id, sender, message) VALUES (?, 'admin', ?)");
        $stmt->bind_param("ss", $sessionId, $message);
    }

    if ($stmt->execute()) {
        respond_json(['success' => true]);
    }

    respond_json(['error' => 'Db error'], 500);
}
