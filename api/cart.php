<?php
header('Content-Type: application/json');
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/cart_helpers.php';

$action = $_GET['action'] ?? '';

init_cart_storage();

switch ($action) {
    case 'get':
        cart_get();
        break;
    case 'add':
        cart_add();
        break;
    case 'update':
        cart_update();
        break;
    case 'remove':
        cart_remove();
        break;
    case 'clear':
        cart_clear();
        break;
    case 'restore_last_order':
        cart_restore_last_order();
        break;
    default:
        respond_json(['error' => 'Unknown action'], 400);
}











function cart_get(): void {
    $db = get_db();
    $_SESSION['cart'] = hydrate_cart_snapshot($db, $_SESSION['cart'] ?? []);
    $cart = array_values($_SESSION['cart']);
    $total = 0;
    $count = 0;
    foreach ($cart as $item) {
        $subtotal = floatval($item['price']) * intval($item['quantity']);
        $item['subtotal'] = $subtotal;
        $total += $subtotal;
        $count += intval($item['quantity']);
    }
    respond_json([
        'items' => $cart,
        'total_price' => $total,
        'total_quantity' => $count
    ]);
}

function cart_add(): void {
    $product_id = intval($_POST['product_id'] ?? 0);
    $quantity = intval($_POST['quantity'] ?? 1);
    if ($product_id <= 0 || $quantity <= 0) {
        respond_json(['error' => 'Invalid data'], 422);
    }
    $db = get_db();
    $stmt = $db->prepare('SELECT product_id, name, price, image_url, category FROM products WHERE product_id = ? AND is_active = 1');
    $stmt->bind_param('i', $product_id);
    $stmt->execute();
    $product = $stmt->get_result()->fetch_assoc();
    if (!$product) {
        respond_json(['error' => 'Product not found'], 404);
    }
    if (isset($_SESSION['cart'][$product_id])) {
        $_SESSION['cart'][$product_id]['quantity'] += $quantity;
    } else {
        $_SESSION['cart'][$product_id] = [
            'product_id' => $product['product_id'],
            'name' => $product['name'],
            'price' => $product['price'],
            'quantity' => $quantity,
            'image_url' => $product['image_url'],
            'category' => $product['category'] ?? ''
        ];
    }

    $memberId = current_member_id();
    if ($memberId > 0) {
        save_member_cart_item($memberId, $product_id, intval($_SESSION['cart'][$product_id]['quantity']));
    }

    respond_json(['success' => true]);
}

function cart_update(): void {
    $product_id = intval($_POST['product_id'] ?? 0);
    $quantity = intval($_POST['quantity'] ?? 0);
    if ($product_id <= 0) {
        respond_json(['error' => 'Product id required'], 422);
    }
    if ($quantity <= 0) {
        unset($_SESSION['cart'][$product_id]);
    } else {
        if (!isset($_SESSION['cart'][$product_id])) {
            respond_json(['error' => 'Item not in cart'], 404);
        }
        $_SESSION['cart'][$product_id]['quantity'] = $quantity;
    }

    $memberId = current_member_id();
    if ($memberId > 0) {
        if ($quantity <= 0) {
            delete_member_cart_item($memberId, $product_id);
        } else {
            save_member_cart_item($memberId, $product_id, $quantity);
        }
    }

    respond_json(['success' => true]);
}

function cart_remove(): void {
    $product_id = intval($_POST['product_id'] ?? 0);
    if ($product_id <= 0) {
        respond_json(['error' => 'Product id required'], 422);
    }
    unset($_SESSION['cart'][$product_id]);

    $memberId = current_member_id();
    if ($memberId > 0) {
        delete_member_cart_item($memberId, $product_id);
    }

    respond_json(['success' => true]);
}

function cart_clear(): void {
    $_SESSION['cart'] = [];

    $memberId = current_member_id();
    if ($memberId > 0) {
        clear_member_cart($memberId);
    }

    respond_json(['success' => true]);
}

function cart_restore_last_order(): void {
    $memberId = current_member_id();
    if ($memberId <= 0) {
        respond_json(['error' => 'Auth required'], 401);
    }

    $db = get_db();
    ensure_member_cart_table($db);

    $orderStmt = $db->prepare('SELECT order_id FROM orders WHERE member_id = ? ORDER BY created_at DESC LIMIT 1');
    $orderStmt->bind_param('i', $memberId);
    $orderStmt->execute();
    $order = $orderStmt->get_result()->fetch_assoc();
    if (!$order) {
        respond_json(['error' => '找不到可恢復的歷史訂單'], 404);
    }

    $orderId = intval($order['order_id']);
    $priceColumn = 'unit_price';
    $checkPrice = $db->query("SHOW COLUMNS FROM order_items LIKE 'price'");
    if ($checkPrice && $checkPrice->num_rows > 0) {
        $priceColumn = 'price';
    }

    $sql = "SELECT oi.product_id, oi.quantity, oi.$priceColumn AS price, p.name, p.image_url, p.category
            FROM order_items oi
            JOIN products p ON p.product_id = oi.product_id
            WHERE oi.order_id = ? AND p.is_active = 1";
    $itemStmt = $db->prepare($sql);
    $itemStmt->bind_param('i', $orderId);
    $itemStmt->execute();
    $items = $itemStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    if (empty($items)) {
        respond_json(['error' => '該訂單沒有可恢復的商品'], 404);
    }

    clear_member_cart($memberId);
    $_SESSION['cart'] = [];
    foreach ($items as $item) {
        $pid = intval($item['product_id']);
        $qty = intval($item['quantity']);
        if ($pid <= 0 || $qty <= 0) {
            continue;
        }
        $_SESSION['cart'][$pid] = [
            'product_id' => $pid,
            'name' => $item['name'],
            'price' => $item['price'],
            'quantity' => $qty,
            'image_url' => $item['image_url'],
            'category' => $item['category'] ?? ''
        ];
        save_member_cart_item($memberId, $pid, $qty);
    }

    respond_json(['success' => true]);
}
