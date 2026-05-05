<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/cart_helpers.php';
require_once __DIR__ . '/promotion_helpers.php';

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'place_order':
        place_order();
        break;
    case 'request_refund':
        request_refund();
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

function ensure_refund_requests_table(mysqli $db): void {
    $db->query(
        'CREATE TABLE IF NOT EXISTS order_refund_requests (
            request_id INT NOT NULL AUTO_INCREMENT,
            order_id INT NOT NULL,
            member_id INT NOT NULL,
            reason VARCHAR(255) NOT NULL,
            note TEXT DEFAULT NULL,
            status ENUM("pending", "approved", "rejected") NOT NULL DEFAULT "pending",
            review_note TEXT DEFAULT NULL,
            reviewed_by INT DEFAULT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            reviewed_at TIMESTAMP NULL DEFAULT NULL,
            PRIMARY KEY (request_id),
            KEY idx_refund_order_member (order_id, member_id),
            KEY idx_refund_member (member_id),
            CONSTRAINT fk_refund_order FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE CASCADE,
            CONSTRAINT fk_refund_member FOREIGN KEY (member_id) REFERENCES members(member_id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );
}

function request_refund(): void {
    $user = require_login();
    
    // Validate user has member_id
    if (empty($user['member_id'])) {
        respond_json(['error' => '使用者資訊不完整，請重新登入'], 401);
    }
    
    $orderId = intval($_POST['order_id'] ?? 0);
    $reason = trim((string)($_POST['reason'] ?? ''));
    $note = trim((string)($_POST['note'] ?? ''));

    if ($orderId <= 0) {
        respond_json(['error' => 'Order id required'], 422);
    }
    if ($reason === '') {
        respond_json(['error' => '請填寫退單原因'], 422);
    }

    $db = get_db();
    ensure_refund_requests_table($db);
    
    // First verify the order exists for this user
    $checkStmt = $db->prepare('SELECT order_id, member_id, status FROM orders WHERE order_id = ? LIMIT 1');
    $checkStmt->bind_param('i', $orderId);
    $checkStmt->execute();
    $checkOrder = $checkStmt->get_result()->fetch_assoc();
    
    if (!$checkOrder) {
        respond_json(['error' => '找不到此訂單'], 404);
    }
    
    // Verify ownership
    $userMemberId = intval($user['member_id']);
    $orderMemberId = intval($checkOrder['member_id']);
    if ($userMemberId !== $orderMemberId) {
        respond_json(['error' => '找不到此訂單'], 404);
    }
    
    $order = $checkOrder;

    // Support both old/new status naming across different schema versions.
    $allowed = ['pending', 'accepted', 'preparing', 'created'];
    if (!in_array((string)$order['status'], $allowed, true)) {
        respond_json(['error' => '目前訂單狀態不可申請退單'], 422);
    }

    $latestStmt = $db->prepare('SELECT status FROM order_refund_requests WHERE order_id = ? AND member_id = ? ORDER BY request_id DESC LIMIT 1');
    $latestStmt->bind_param('ii', $orderId, $user['member_id']);
    $latestStmt->execute();
    $latest = $latestStmt->get_result()->fetch_assoc();
    if ($latest && in_array((string)$latest['status'], ['pending', 'approved'], true)) {
        respond_json(['error' => '此訂單已有進行中的退單申請'], 422);
    }

    $insertStmt = $db->prepare('INSERT INTO order_refund_requests (order_id, member_id, reason, note, status) VALUES (?, ?, ?, ?, "pending")');
    $insertStmt->bind_param('iiss', $orderId, $user['member_id'], $reason, $note);
    if (!$insertStmt->execute()) {
        respond_json(['error' => '申請退單失敗，請稍後再試'], 500);
    }

    respond_json(['success' => true]);
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
    $pickup_store_brand = trim((string)($_POST['pickup_store_brand'] ?? ''));
    $pickup_store_id = trim((string)($_POST['pickup_store_id'] ?? ''));
    $pickup_store_name = trim((string)($_POST['pickup_store_name'] ?? ''));
    $pickup_store_address = trim((string)($_POST['pickup_store_address'] ?? ''));
    if ($payment_method === '' || $shipping_method === '') {
        respond_json(['error' => 'Payment and shipping methods are required'], 422);
    }
    if ($address_id <= 0) {
        respond_json(['error' => 'Address required'], 422);
    }
    if ($shipping_method === '超商取貨') {
        if ($pickup_store_brand === '' || $pickup_store_id === '' || $pickup_store_name === '') {
            respond_json(['error' => '超商取貨請填寫超商品牌、門市代碼與門市名稱'], 422);
        }
        if (strlen($pickup_store_brand) > 30 || strlen($pickup_store_id) > 30 || strlen($pickup_store_name) > 100 || strlen($pickup_store_address) > 255) {
            respond_json(['error' => '門市資訊格式不正確'], 422);
        }
    }

    $db = get_db();
    $addressStmt = $db->prepare('SELECT address_id, recipient_name, phone, address_line FROM member_addresses WHERE address_id = ? AND member_id = ?');
    $addressStmt->bind_param('ii', $address_id, $user['member_id']);
    $addressStmt->execute();
    $addressRow = $addressStmt->get_result()->fetch_assoc();
    if (!$addressRow) {
        respond_json(['error' => 'Address not found'], 404);
    }

    $productIds = array_map('intval', array_keys($cart));
    if (empty($productIds)) {
        respond_json(['error' => 'Cart is empty'], 422);
    }
    $placeholders = implode(',', array_fill(0, count($productIds), '?'));
    $stmt = $db->prepare("SELECT product_id, price, name, category FROM products WHERE product_id IN ($placeholders) AND is_active = 1");
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
            'price' => $price,
            'category' => $products[$pid]['category'] ?? '',
            'name' => $products[$pid]['name'] ?? ''
        ];
        $subtotal += $price * $qty;
    }

    $paymentFees = ['信用卡' => 0, '貨到付款' => 30];
    $shippingQuote = promo_get_shipping_quote($shipping_method, $subtotal);
    $shippingFee = floatval($shippingQuote['shipping_fee']);
    $paymentFee = $paymentFees[$payment_method] ?? 0;

    $promotionResult = promo_calculate_bundle_discount($orderItems);
    $promotionDiscount = floatval($promotionResult['discount'] ?? 0);

    $couponCode = trim((string)($_POST['coupon_code'] ?? ''));
    $couponDiscount = 0.0;
    $couponRow = null;

    if ($couponCode !== '' && table_exists($db, 'coupons')) {
        $couponStmt = $db->prepare('SELECT coupon_id, coupon_code, description, discount_type, discount_value, min_purchase, used_count, max_usage, expiry_date, is_active FROM coupons WHERE coupon_code = ? AND member_id = ? LIMIT 1');
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
        $minPurchase = round(floatval($couponRow['min_purchase']), 2);
        $currentSubtotal = round($subtotal, 2);
        // Add a tiny epsilon to avoid floating-point precision causing false negatives at the threshold.
        if (($currentSubtotal + 0.00001) < $minPurchase) {
            respond_json([
                'error' => sprintf('未達優惠券最低消費門檻（目前 $%.2f，需滿 $%.2f）', $currentSubtotal, $minPurchase)
            ], 422);
        }

        if (promo_is_welcome_coupon($couponRow)) {
            $orderCountStmt = $db->prepare('SELECT COUNT(*) AS order_count FROM orders WHERE member_id = ?');
            $orderCountStmt->bind_param('i', $user['member_id']);
            $orderCountStmt->execute();
            $orderCount = intval($orderCountStmt->get_result()->fetch_assoc()['order_count'] ?? 0);
            if ($orderCount > 0) {
                respond_json(['error' => '首購券僅限第一筆訂單使用'], 422);
            }
        }

        if ($couponRow['discount_type'] === 'percent') {
            $couponDiscount = $subtotal * (floatval($couponRow['discount_value']) / 100);
        } else {
            $couponDiscount = floatval($couponRow['discount_value']);
        }
        if ($couponDiscount > $subtotal) {
            $couponDiscount = $subtotal;
        }
    }

    $discount = min($subtotal, $promotionDiscount + $couponDiscount);

    $total = $subtotal + $shippingFee + $paymentFee - $discount;
    if ($total < 0) {
        $total = 0;
    }

    $hasPayment = column_exists($db, 'orders', 'payment_method');
    $hasShipping = column_exists($db, 'orders', 'shipping_method');
    $hasTotalPrice = column_exists($db, 'orders', 'total_price');
    $hasTotalAmount = column_exists($db, 'orders', 'total_amount');
    $hasDiscountAmount = column_exists($db, 'orders', 'discount_amount');
    $hasShippingFeeColumn = column_exists($db, 'orders', 'shipping_fee');
    $hasAddress = column_exists($db, 'orders', 'address_id');
    $hasShipName = column_exists($db, 'orders', 'ship_name');
    $hasShipPhone = column_exists($db, 'orders', 'ship_phone');
    $hasShipAddressLine = column_exists($db, 'orders', 'ship_address_line');
    $priceColumn = column_exists($db, 'order_items', 'price') ? 'price' : 'unit_price';

    $columns = ['member_id'];
    $types = 'i';
    $values = [$user['member_id']];

    $shipAddressLine = (string)$addressRow['address_line'];
    if ($shipping_method === '超商取貨') {
        $storeText = '[超商門市] ' . $pickup_store_brand . ' ' . $pickup_store_name . ' (' . $pickup_store_id . ')';
        if ($pickup_store_address !== '') {
            $storeText .= ' ' . $pickup_store_address;
        }
        $shipAddressLine = $storeText;
    }

    if ($hasAddress) {
        $columns[] = 'address_id';
        $types .= 'i';
        $values[] = $address_id;
    }
    if ($hasShipName) {
        $columns[] = 'ship_name';
        $types .= 's';
        $values[] = (string)$addressRow['recipient_name'];
    }
    if ($hasShipPhone) {
        $columns[] = 'ship_phone';
        $types .= 's';
        $values[] = (string)($addressRow['phone'] ?? '');
    }
    if ($hasShipAddressLine) {
        $columns[] = 'ship_address_line';
        $types .= 's';
        $values[] = $shipAddressLine;
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

    if ($hasDiscountAmount) {
        $columns[] = 'discount_amount';
        $types .= 'd';
        $values[] = $discount;
    }
    if ($hasShippingFeeColumn) {
        $columns[] = 'shipping_fee';
        $types .= 'd';
        $values[] = $shippingFee;
    }

    $initialStatus = 'pending';
    $statusColumnRes = $db->query("SHOW COLUMNS FROM orders LIKE 'status'");
    if ($statusColumnRes) {
        $statusColumn = $statusColumnRes->fetch_assoc();
        $statusType = strtolower((string)($statusColumn['Type'] ?? ''));
        if (strpos($statusType, "'accepted'") !== false) {
            $initialStatus = 'accepted';
        }
    }

    $columns[] = 'status';
    $types .= 's';
    $values[] = $initialStatus;

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
        $stockStmt = $db->prepare('UPDATE products SET stock = stock - ? WHERE product_id = ?');
        $hasStockMovements = table_exists($db, 'stock_movements');
        $stockMovementStmt = null;
        if ($hasStockMovements) {
            $stockMovementStmt = $db->prepare('INSERT INTO stock_movements (product_id, variant_id, movement_type, quantity, reference_type, reference_id, notes, created_by) VALUES (?, NULL, ?, ?, ?, ?, ?, ?)');
        }

        foreach ($orderItems as $oi) {
            $itemStmt->bind_param('iiid', $order_id, $oi['product_id'], $oi['quantity'], $oi['price']);
            if (!$itemStmt->execute()) {
                throw new Exception('Failed to insert order items');
            }

            // Deduct stock from products table
            $stockStmt->bind_param('ii', $oi['quantity'], $oi['product_id']);
            if (!$stockStmt->execute()) {
                throw new Exception('Failed to update product stock');
            }

            // Record stock movement if table exists
            if ($hasStockMovements && $stockMovementStmt) {
                $movementType = 'order';
                $referenceType = 'order';
                $notes = 'Order #' . $order_id;
                $createdBy = $user['member_id'];
                $stockMovementStmt->bind_param('isissis', $oi['product_id'], $movementType, $oi['quantity'], $referenceType, $order_id, $notes, $createdBy);
                if (!$stockMovementStmt->execute()) {
                    error_log('Failed to record stock movement for product ' . $oi['product_id']);
                }
            }
        }

        if ($couponRow) {
            $nextUsedCount = intval($couponRow['used_count']) + 1;
            $maxUsage = intval($couponRow['max_usage']);
            $couponId = intval($couponRow['coupon_id']);
            
            // If coupon is fully used, delete it; otherwise update the count
            if ($nextUsedCount >= $maxUsage) {
                $deleteCouponStmt = $db->prepare('DELETE FROM coupons WHERE coupon_id = ? AND member_id = ?');
                $deleteCouponStmt->bind_param('ii', $couponId, $user['member_id']);
                if (!$deleteCouponStmt->execute()) {
                    throw new Exception('Failed to delete used coupon');
                }
            } else {
                $updateCouponStmt = $db->prepare('UPDATE coupons SET used_count = ? WHERE coupon_id = ? AND member_id = ?');
                $updateCouponStmt->bind_param('iii', $nextUsedCount, $couponId, $user['member_id']);
                if (!$updateCouponStmt->execute()) {
                    throw new Exception('Failed to update coupon usage');
                }
            }
        }

        $db->commit();
    } catch (Throwable $e) {
        $db->rollback();
        error_log('place_order failed: ' . $e->getMessage());
        respond_json(['error' => 'Failed to place order'], 500);
    }

    foreach (array_keys($cart) as $orderedPid) {
        unset($_SESSION['cart'][$orderedPid]);
        if (!empty($user['member_id'])) {
            delete_member_cart_item(intval($user['member_id']), intval($orderedPid));
        }
    }
    respond_json([
        'success' => true,
        'order_id' => $order_id,
        'shipping_fee' => $shippingFee,
        'payment_fee' => $paymentFee,
        'subtotal' => $subtotal,
        'promotion_discount' => $promotionDiscount,
        'coupon_discount' => $couponDiscount,
        'promotion_details' => $promotionResult['details'] ?? [],
        'promotion_reward_lines' => $promotionResult['reward_lines'] ?? [],
        'discount' => $discount,
        'total' => $total
    ]);
}

function list_my_orders(): void {
    $user = require_login();
    $db = get_db();
    ensure_refund_requests_table($db);
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

    $refundStmt = $db->prepare('SELECT order_id, status, reason, created_at FROM order_refund_requests WHERE member_id = ? ORDER BY request_id DESC');
    $refundStmt->bind_param('i', $user['member_id']);
    $refundStmt->execute();
    $refundRows = $refundStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $refundMap = [];
    foreach ($refundRows as $row) {
        $oid = intval($row['order_id']);
        if (!isset($refundMap[$oid])) {
            $refundMap[$oid] = $row;
        }
    }

    foreach ($orders as &$order) {
        // Ensure order_id is integer
        $order['order_id'] = intval($order['order_id']);
        
        $oid = $order['order_id'];
        if (isset($refundMap[$oid])) {
            $order['refund_status'] = $refundMap[$oid]['status'];
            $order['refund_reason'] = $refundMap[$oid]['reason'];
            $order['refund_created_at'] = $refundMap[$oid]['created_at'];
            if (($order['refund_status'] ?? '') === 'approved') {
                $order['status'] = 'cancelled';
            }
        } else {
            $order['refund_status'] = null;
            $order['refund_reason'] = null;
            $order['refund_created_at'] = null;
        }
    }
    unset($order);

    respond_json(['orders' => $orders]);
}

function get_order_detail(): void {
    $user = require_login();
    $order_id = intval($_GET['order_id'] ?? $_POST['order_id'] ?? 0);
    if ($order_id <= 0) {
        respond_json(['error' => 'Order id required'], 422);
    }
    $db = get_db();
    ensure_refund_requests_table($db);
    $hasPayment = column_exists($db, 'orders', 'payment_method');
    $hasShipping = column_exists($db, 'orders', 'shipping_method');
    $hasTotalPrice = column_exists($db, 'orders', 'total_price');
    $hasTotalAmount = column_exists($db, 'orders', 'total_amount');
    $totalField = $hasTotalPrice ? 'total_price' : 'total_amount';
    $fields = ['order_id', 'status', 'created_at', "$totalField AS total_price"];
    if ($hasPayment) $fields[] = 'payment_method';
    if ($hasShipping) $fields[] = 'shipping_method';
    if (column_exists($db, 'orders', 'address_id')) $fields[] = 'address_id';
    if (column_exists($db, 'orders', 'ship_name')) $fields[] = 'ship_name';
    if (column_exists($db, 'orders', 'ship_phone')) $fields[] = 'ship_phone';
    if (column_exists($db, 'orders', 'ship_address_line')) $fields[] = 'ship_address_line';

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

    $refundStmt = $db->prepare('SELECT status, reason, note, created_at FROM order_refund_requests WHERE order_id = ? AND member_id = ? ORDER BY request_id DESC LIMIT 1');
    $refundStmt->bind_param('ii', $order_id, $user['member_id']);
    $refundStmt->execute();
    $refund = $refundStmt->get_result()->fetch_assoc();
    if ($refund) {
        $order['refund_status'] = $refund['status'];
        $order['refund_reason'] = $refund['reason'];
        $order['refund_note'] = $refund['note'];
        $order['refund_created_at'] = $refund['created_at'];
        if (($refund['status'] ?? '') === 'approved') {
            $order['status'] = 'cancelled';
        }
    } else {
        $order['refund_status'] = null;
    }

    respond_json(['order' => $order, 'items' => $items]);
}
