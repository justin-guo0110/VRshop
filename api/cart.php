<?php
require_once __DIR__ . '/db.php';

$action = $_GET['action'] ?? '';
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

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
    default:
        respond_json(['error' => 'Unknown action'], 400);
}

function cart_get(): void {
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
    $stmt = $db->prepare('SELECT product_id, name, price, image_url, stock FROM products WHERE product_id = ? AND is_active = 1');
    $stmt->bind_param('i', $product_id);
    $stmt->execute();
    $product = $stmt->get_result()->fetch_assoc();
    if (!$product) {
        respond_json(['error' => 'Product not found'], 404);
    }
    $current_qty = isset($_SESSION['cart'][$product_id]) ? intval($_SESSION['cart'][$product_id]['quantity']) : 0;
    $stock = intval($product['stock'] ?? 0);
    if ($stock <= 0) {
        respond_json(['error' => '目前缺貨，無法加入購物車'], 409);
    }
    if (($current_qty + $quantity) > $stock) {
        respond_json(['error' => '加入數量超過庫存'], 409);
    }
    if (isset($_SESSION['cart'][$product_id])) {
        $_SESSION['cart'][$product_id]['quantity'] += $quantity;
    } else {
        $_SESSION['cart'][$product_id] = [
            'product_id' => $product['product_id'],
            'name' => $product['name'],
            'price' => $product['price'],
            'quantity' => $quantity,
            'image_url' => $product['image_url']
        ];
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
    respond_json(['success' => true]);
}

function cart_remove(): void {
    $product_id = intval($_POST['product_id'] ?? 0);
    if ($product_id <= 0) {
        respond_json(['error' => 'Product id required'], 422);
    }
    unset($_SESSION['cart'][$product_id]);
    respond_json(['success' => true]);
}

function cart_clear(): void {
    $_SESSION['cart'] = [];
    respond_json(['success' => true]);
}
