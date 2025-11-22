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
