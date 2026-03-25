<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/cart_helpers.php';

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'place_order':
        place_order();
        break;
    case 'list_my_orders':
        list_my_orders();
        break;
    case 'get_order_detail':
        get_order_detail();
        break;
    default:
        respond_json(['error' => 'Unknown action'], 400);
}

function column_exists(mysqli $db, string $table, string $column): bool {
    static $cache = [];
    $key = $table . '.' . $column;
    if (isset($cache[$key])) {
        return $cache[$key];
    }
    $tableEsc = $db->real_escape_string($table);
    $colEsc = $db->real_escape_string($column);
    $result = $db->query("SHOW COLUMNS FROM `$tableEsc` LIKE '$colEsc'");
    $cache[$key] = $result && $result->num_rows > 0;
    return $cache[$key];
}

function table_exists(mysqli $db, string $table): bool {
    static $cache = [];
    if (isset($cache[$table])) {
        return $cache[$table];
    }
    $tableEsc = $db->real_escape_string($table);
    $result = $db->query("SHOW TABLES LIKE '$tableEsc'");
    $cache[$table] = $result && $result->num_rows > 0;
    return $cache[$table];
}

function place_order(): void {
    $user = require_login();
    init_cart_storage();
    $cartAll = $_SESSION['cart'] ?? [];
    if (empty($cartAll)) {
        respond_json(['error' => 'Cart is empty'], 422);
    }

    $selectedRaw = trim((string)($_POST['selected_product_ids'] ?? ''));
    $cart = [];
    if ($selectedRaw !== '') {
        $selectedProductIds = array_values(array_unique(array_filter(array_map('intval', explode(',', $selectedRaw)), function ($v) {
            return $v > 0;
        })));
        if (empty($selectedProductIds)) {
            respond_json(['error' => 'No valid selected products'], 422);
        }
        foreach ($selectedProductIds as $pid) {
            if (!isset($cartAll[$pid])) {
                respond_json(['error' => 'Selected product not in cart: ' . $pid], 422);
            }
            $cart[$pid] = $cartAll[$pid];
        }
    } else {
        $cart = $cartAll;
    }

    $payment_method = trim($_POST['payment_method'] ?? '');
    $shipping_method = trim($_POST['shipping_method'] ?? '');
    $address_id = intval($_POST['address_id'] ?? 0);
    if ($payment_method === '' || $shipping_method === '') {
        respond_json(['error' => 'Payment and shipping methods are required'], 422);
    }
    if ($address_id <= 0) {
        respond_json(['error' => 'Address required'], 422);
    }

    $db = get_db();
    $addressStmt = $db->prepare('SELECT address_id FROM member_addresses WHERE address_id = ? AND member_id = ?');
    $addressStmt->bind_param('ii', $address_id, $user['member_id']);
    $addressStmt->execute();
    if (!$addressStmt->get_result()->fetch_assoc()) {
        respond_json(['error' => 'Address not found'], 404);
    }

    $productIds = array_map('intval', array_keys($cart));
    if (empty($productIds)) {
        respond_json(['error' => 'Cart is empty'], 422);
    }
    $placeholders = implode(',', array_fill(0, count($productIds), '?'));
    $stmt = $db->prepare("SELECT product_id, price, name FROM products WHERE product_id IN ($placeholders) AND is_active = 1");
    $types = str_repeat('i', count($productIds));
    $stmt->bind_param($types, ...$productIds);
    $stmt->execute();
    $res = $stmt->get_result();
    $products = [];
    while ($row = $res->fetch_assoc()) {
        $products[$row['product_id']] = $row;
    }
    foreach ($productIds as $pid) {
        if (!isset($products[$pid])) {
            respond_json(['error' => 'Product not found: ' . $pid], 404);
        }
    }

    $subtotal = 0.0;
    $orderItems = [];
    foreach ($cart as $pid => $item) {
        $price = floatval($products[$pid]['price']);
        $qty = intval($item['quantity']);
        $orderItems[] = [
            'product_id' => $pid,
            'quantity' => $qty,
            'price' => $price
        ];
        $subtotal += $price * $qty;
    }

    $shippingFees = ['宅配' => 100, '超商取貨' => 60];
    $paymentFees = ['信用卡' => 0, '貨到付款' => 30];
    $shippingFee = $shippingFees[$shipping_method] ?? 0;
    $paymentFee = $paymentFees[$payment_method] ?? 0;

    $couponCode = trim((string)($_POST['coupon_code'] ?? ''));
    $discount = 0.0;
    $couponRow = null;

    if ($couponCode !== '' && table_exists($db, 'coupons')) {
        $couponStmt = $db->prepare('SELECT coupon_id, discount_type, discount_value, min_purchase, used_count, max_usage, expiry_date, is_active FROM coupons WHERE coupon_code = ? AND member_id = ? LIMIT 1');
        $couponStmt->bind_param('si', $couponCode, $user['member_id']);
        $couponStmt->execute();
        $couponRow = $couponStmt->get_result()->fetch_assoc();

        if (!$couponRow) {
            respond_json(['error' => '優惠券不存在或不屬於您'], 422);
        }
        if (intval($couponRow['is_active']) !== 1) {
            respond_json(['error' => '優惠券已失效'], 422);
        }
        if (strtotime((string)$couponRow['expiry_date']) < time()) {
            respond_json(['error' => '優惠券已過期'], 422);
        }
        if (intval($couponRow['used_count']) >= intval($couponRow['max_usage'])) {
            respond_json(['error' => '優惠券已使用完畢'], 422);
        }
        if ($subtotal < floatval($couponRow['min_purchase'])) {
            respond_json(['error' => '未達優惠券最低消費門檻'], 422);
        }

        if ($couponRow['discount_type'] === 'percent') {
            $discount = $subtotal * (floatval($couponRow['discount_value']) / 100);
        } else {
            $discount = floatval($couponRow['discount_value']);
        }
        if ($discount > $subtotal) {
            $discount = $subtotal;
        }
    }

    $total = $subtotal + $shippingFee + $paymentFee - $discount;

    $hasPayment = column_exists($db, 'orders', 'payment_method');
    $hasShipping = column_exists($db, 'orders', 'shipping_method');
    $hasTotalPrice = column_exists($db, 'orders', 'total_price');
    $hasTotalAmount = column_exists($db, 'orders', 'total_amount');
    $hasAddress = column_exists($db, 'orders', 'address_id');
    $priceColumn = column_exists($db, 'order_items', 'price') ? 'price' : 'unit_price';

    $columns = ['member_id'];
    $types = 'i';
    $values = [$user['member_id']];

    if ($hasAddress) {
        $columns[] = 'address_id';
        $types .= 'i';
        $values[] = $address_id;
    }
    if ($hasPayment) {
        $columns[] = 'payment_method';
        $types .= 's';
        $values[] = $payment_method;
    }
    if ($hasShipping) {
        $columns[] = 'shipping_method';
        $types .= 's';
        $values[] = $shipping_method;
    }
    $totalField = $hasTotalPrice ? 'total_price' : 'total_amount';
    $columns[] = $totalField;
    $types .= 'd';
    $values[] = $total;

    $columns[] = 'status';
    $types .= 's';
    $values[] = 'pending';

    $placeholders = implode(',', array_fill(0, count($columns), '?'));
    $sql = 'INSERT INTO orders (' . implode(',', $columns) . ') VALUES (' . $placeholders . ')';
    $db->begin_transaction();
    try {
        $insertOrder = $db->prepare($sql);
        $insertOrder->bind_param($types, ...$values);
        if (!$insertOrder->execute()) {
            throw new Exception('Failed to place order');
        }
        $order_id = $insertOrder->insert_id;

        $itemStmt = $db->prepare("INSERT INTO order_items (order_id, product_id, quantity, $priceColumn) VALUES (?, ?, ?, ?)");
        foreach ($orderItems as $oi) {
            $itemStmt->bind_param('iiid', $order_id, $oi['product_id'], $oi['quantity'], $oi['price']);
            if (!$itemStmt->execute()) {
                throw new Exception('Failed to insert order items');
            }
        }

        if ($couponRow) {
            $nextUsedCount = intval($couponRow['used_count']) + 1;
            $couponId = intval($couponRow['coupon_id']);
            $updateCouponStmt = $db->prepare('UPDATE coupons SET used_count = ? WHERE coupon_id = ? AND member_id = ?');
            $updateCouponStmt->bind_param('iii', $nextUsedCount, $couponId, $user['member_id']);
            if (!$updateCouponStmt->execute()) {
                throw new Exception('Failed to update coupon usage');
            }
        }

        $db->commit();
    } catch (Throwable $e) {
        $db->rollback();
        respond_json(['error' => 'Failed to place order'], 500);
    }

    foreach (array_keys($cart) as $orderedPid) {
        unset($_SESSION['cart'][$orderedPid]);
    }
    respond_json([
        'success' => true,
        'order_id' => $order_id,
        'shipping_fee' => $shippingFee,
        'payment_fee' => $paymentFee,
        'subtotal' => $subtotal,
        'discount' => $discount,
        'total' => $total
    ]);
}

function list_my_orders(): void {
    $user = require_login();
    $db = get_db();
    $hasPayment = column_exists($db, 'orders', 'payment_method');
    $hasShipping = column_exists($db, 'orders', 'shipping_method');
    $hasTotalPrice = column_exists($db, 'orders', 'total_price');
    $hasTotalAmount = column_exists($db, 'orders', 'total_amount');
    $totalField = $hasTotalPrice ? 'total_price' : 'total_amount';

    $fields = ['order_id', 'status', 'created_at', "$totalField AS total_price"];
    if ($hasPayment) $fields[] = 'payment_method';
    if ($hasShipping) $fields[] = 'shipping_method';

    $sql = 'SELECT ' . implode(',', $fields) . ' FROM orders WHERE member_id = ? ORDER BY created_at DESC';
    $stmt = $db->prepare($sql);
    $stmt->bind_param('i', $user['member_id']);
    $stmt->execute();
    $orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    respond_json(['orders' => $orders]);
}

function get_order_detail(): void {
    $user = require_login();
    $order_id = intval($_GET['order_id'] ?? $_POST['order_id'] ?? 0);
    if ($order_id <= 0) {
        respond_json(['error' => 'Order id required'], 422);
    }
    $db = get_db();
    $hasPayment = column_exists($db, 'orders', 'payment_method');
    $hasShipping = column_exists($db, 'orders', 'shipping_method');
    $hasTotalPrice = column_exists($db, 'orders', 'total_price');
    $hasTotalAmount = column_exists($db, 'orders', 'total_amount');
    $totalField = $hasTotalPrice ? 'total_price' : 'total_amount';
    $fields = ['order_id', 'status', 'created_at', "$totalField AS total_price"];
    if ($hasPayment) $fields[] = 'payment_method';
    if ($hasShipping) $fields[] = 'shipping_method';
    if (column_exists($db, 'orders', 'address_id')) $fields[] = 'address_id';

    $sql = 'SELECT ' . implode(',', $fields) . ' FROM orders WHERE order_id = ? AND member_id = ?';
    $stmt = $db->prepare($sql);
    $stmt->bind_param('ii', $order_id, $user['member_id']);
    $stmt->execute();
    $order = $stmt->get_result()->fetch_assoc();
    if (!$order) {
        respond_json(['error' => 'Order not found'], 404);
    }

    $priceColumn = column_exists($db, 'order_items', 'price') ? 'price' : 'unit_price';
    $itemSql = "SELECT oi.product_id, oi.quantity, oi.$priceColumn AS price, p.name FROM order_items oi JOIN products p ON oi.product_id = p.product_id WHERE oi.order_id = ?";
    $itemStmt = $db->prepare($itemSql);
    $itemStmt->bind_param('i', $order_id);
    $itemStmt->execute();
    $items = $itemStmt->get_result()->fetch_all(MYSQLI_ASSOC);

    respond_json(['order' => $order, 'items' => $items]);
}
