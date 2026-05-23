<?php
header('Content-Type: application/json');
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/cart_helpers.php';
require_once __DIR__ . '/promotion_helpers.php';

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
    case 'add_promo_bundle':
        cart_add_promo_bundle();
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
    $stmt = $db->prepare('SELECT product_id, name, price, image_url, category, stock FROM products WHERE product_id = ? AND is_active = 1');
    $stmt->bind_param('i', $product_id);
    $stmt->execute();
    $product = $stmt->get_result()->fetch_assoc();
    if (!$product) {
        respond_json(['error' => 'Product not found'], 404);
    }
    $stock = intval($product['stock'] ?? 0);
    $existingQuantity = intval($_SESSION['cart'][$product_id]['quantity'] ?? 0);
    $newQuantity = $existingQuantity + $quantity;
    if ($stock <= 0) {
        respond_json(['error' => '商品已售完'], 422);
    }
    if ($newQuantity > $stock) {
        respond_json(['error' => '庫存不足，僅剩 ' . $stock . ' 件可購買'], 422);
    }
    if (!add_product_to_cart(intval($product['product_id']), $quantity, $product)) {
        respond_json(['error' => '加入購物車失敗，請確認庫存後再試'], 422);
    }

    respond_json(['success' => true]);
}

function add_product_to_cart(int $productId, int $quantity, ?array $product = null): bool {
    if ($productId <= 0 || $quantity <= 0) {
        return false;
    }

    if (!is_array($product)) {
        $db = get_db();
        $stmt = $db->prepare('SELECT product_id, name, price, image_url, category, stock FROM products WHERE product_id = ? AND is_active = 1');
        if (!$stmt) {
            return false;
        }
        $stmt->bind_param('i', $productId);
        $stmt->execute();
        $product = $stmt->get_result()->fetch_assoc();
    }

    if (!$product) {
        return false;
    }

    $stock = intval($product['stock'] ?? 0);
    $currentQty = intval($_SESSION['cart'][$productId]['quantity'] ?? 0);
    if ($stock <= 0 || $currentQty + $quantity > $stock) {
        return false;
    }

    if (isset($_SESSION['cart'][$productId])) {
        $_SESSION['cart'][$productId]['quantity'] += $quantity;
    } else {
        $_SESSION['cart'][$productId] = [
            'product_id' => intval($product['product_id']),
            'name' => $product['name'],
            'price' => $product['price'],
            'quantity' => $quantity,
            'image_url' => $product['image_url'],
            'category' => $product['category'] ?? ''
        ];
    }

    $memberId = current_member_id();
    if ($memberId > 0) {
        save_member_cart_item($memberId, $productId, intval($_SESSION['cart'][$productId]['quantity']));
    }
    return true;
}

function cart_add_promo_bundle(): void {
    $ruleCode = trim((string)($_POST['rule_code'] ?? ''));
    if ($ruleCode === '') {
        respond_json(['error' => 'rule_code required'], 422);
    }

    $rules = promo_get_rules();
    $bundleRules = is_array($rules['bundle_rules'] ?? null) ? $rules['bundle_rules'] : [];
    $targetRule = null;
    foreach ($bundleRules as $rule) {
        if (!is_array($rule)) {
            continue;
        }
        if ((string)($rule['code'] ?? '') === $ruleCode) {
            $targetRule = $rule;
            break;
        }
    }
    if (!$targetRule) {
        respond_json(['error' => '促銷規則不存在'], 404);
    }

    $db = get_db();
    $trigger = (isset($targetRule['trigger']) && is_array($targetRule['trigger'])) ? $targetRule['trigger'] : [];
    $condition = (isset($trigger['condition']) && is_array($trigger['condition']))
        ? $trigger['condition']
        : (is_array($targetRule['condition'] ?? null) ? $targetRule['condition'] : []);
    $reward = (isset($targetRule['reward']) && is_array($targetRule['reward'])) ? $targetRule['reward'] : [];
    $minQty = max(1, intval($trigger['min_qty'] ?? ($condition['min_quantity'] ?? 1)));

    $toAdd = [];
    $triggerProductId = 0;
    $conditionProductIds = is_array($condition['product_ids'] ?? null) ? array_values(array_map('intval', $condition['product_ids'])) : [];
    if (!empty($conditionProductIds)) {
        $ids = array_filter($conditionProductIds, fn($id) => $id > 0);
        if (!empty($ids)) {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $types = str_repeat('i', count($ids));
            $stmt = $db->prepare("SELECT product_id FROM products WHERE is_active = 1 AND stock > 0 AND product_id IN ($placeholders) ORDER BY stock DESC, product_id DESC LIMIT 1");
            if ($stmt) {
                $stmt->bind_param($types, ...$ids);
                $stmt->execute();
                $row = $stmt->get_result()->fetch_assoc();
                if ($row) {
                    $triggerProductId = intval($row['product_id']);
                }
            }
        }
    } else {
        $conditionCategories = is_array($condition['categories'] ?? null) ? $condition['categories'] : [];
        foreach ($conditionCategories as $category) {
            $cat = trim((string)$category);
            if ($cat === '') {
                continue;
            }
            $stmt = $db->prepare('SELECT product_id FROM products WHERE is_active = 1 AND stock > 0 AND category = ? ORDER BY stock DESC, product_id DESC LIMIT 1');
            if ($stmt) {
                $stmt->bind_param('s', $cat);
                $stmt->execute();
                $row = $stmt->get_result()->fetch_assoc();
                if ($row) {
                    $triggerProductId = intval($row['product_id']);
                    break;
                }
            }
        }
    }

    if ($triggerProductId > 0) {
        $toAdd[] = ['product_id' => $triggerProductId, 'quantity' => $minQty, 'role' => 'trigger'];
    }

    $type = (string)($reward['type'] ?? ($targetRule['type'] ?? ''));
    $rewardProductId = intval($reward['product_id'] ?? ($targetRule['reward_product_id'] ?? 0));
    if ($type === 'gift' && $rewardProductId <= 0) {
        respond_json(['error' => '此買送活動缺少贈品商品設定，請通知管理員修正規則'], 422);
    }
    if ($type === 'gift' && $rewardProductId > 0) {
        $toAdd[] = [
            'product_id' => $rewardProductId,
            'quantity' => max(1, intval($reward['qty'] ?? ($targetRule['reward_quantity'] ?? 1))),
            'role' => 'gift'
        ];
    }

    if (empty($toAdd)) {
        respond_json(['error' => '此活動目前無可加入的商品'], 422);
    }

    $validRows = array_values(array_filter($toAdd, function ($row) {
        return intval($row['product_id'] ?? 0) > 0 && intval($row['quantity'] ?? 0) > 0;
    }));
    if (empty($validRows)) {
        respond_json(['error' => '此活動目前無可加入的商品'], 422);
    }

    // 全有全無：先驗證所有商品可加入，避免只加到部分商品
    $productIds = array_values(array_unique(array_map(fn($row) => intval($row['product_id']), $validRows)));
    $placeholders = implode(',', array_fill(0, count($productIds), '?'));
    $types = str_repeat('i', count($productIds));
    $productStmt = $db->prepare("SELECT product_id, name, price, image_url, category FROM products WHERE is_active = 1 AND product_id IN ($placeholders)");
    if (!$productStmt) {
        respond_json(['error' => '促銷商品驗證失敗，請稍後再試'], 500);
    }
    $productStmt->bind_param($types, ...$productIds);
    $productStmt->execute();
    $productRows = $productStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $productMap = [];
    foreach ($productRows as $p) {
        $productMap[intval($p['product_id'])] = $p;
    }

    foreach ($validRows as $row) {
        $pid = intval($row['product_id']);
        if (!isset($productMap[$pid])) {
            $roleText = ($row['role'] ?? '') === 'gift' ? '贈品' : '活動商品';
            respond_json(['error' => $roleText . '目前已下架或不存在，無法加入整組活動'], 422);
        }
    }

    $added = [];
    foreach ($validRows as $row) {
        $pid = intval($row['product_id']);
        $qty = max(1, intval($row['quantity']));
        $ok = add_product_to_cart($pid, $qty, $productMap[$pid]);
        if (!$ok) {
            respond_json(['error' => '加入組合商品失敗，庫存不足或商品已售完'], 422);
        }
        $added[] = [
            'product_id' => $pid,
            'quantity' => $qty,
            'role' => $row['role'],
            'name' => $productMap[$pid]['name'] ?? ''
        ];
    }

    respond_json([
        'success' => true,
        'added_items' => $added
    ]);
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
        $db = get_db();
        $stmt = $db->prepare('SELECT stock FROM products WHERE product_id = ? AND is_active = 1');
        $stmt->bind_param('i', $product_id);
        $stmt->execute();
        $product = $stmt->get_result()->fetch_assoc();
        if (!$product) {
            respond_json(['error' => 'Product not found'], 404);
        }
        $stock = intval($product['stock'] ?? 0);
        if ($stock <= 0) {
            unset($_SESSION['cart'][$product_id]);
            $memberId = current_member_id();
            if ($memberId > 0) {
                delete_member_cart_item($memberId, $product_id);
            }
            respond_json(['error' => '商品已售完，已從購物車移除'], 422);
        }
        if ($quantity > $stock) {
            respond_json(['error' => '庫存不足，僅剩 ' . $stock . ' 件可購買'], 422);
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
