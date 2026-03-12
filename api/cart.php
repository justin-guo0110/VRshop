<?php
require_once __DIR__ . '/db.php';

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

function init_cart_storage(): void {
    if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }

    $user = current_user();
    if (!$user || empty($user['member_id'])) {
        unset($_SESSION['cart_member_id']);
        return;
    }

    $memberId = intval($user['member_id']);
    if ($memberId <= 0) {
        return;
    }

    $db = get_db();
    ensure_member_cart_table($db);

    $loadedMemberId = intval($_SESSION['cart_member_id'] ?? 0);
    if ($loadedMemberId !== $memberId) {
        merge_session_cart_to_member($db, $memberId, $_SESSION['cart']);
        $_SESSION['cart_member_id'] = $memberId;
    }

    $_SESSION['cart'] = fetch_member_cart_items($db, $memberId);
}

function ensure_member_cart_table(mysqli $db): void {
    $db->query(
        'CREATE TABLE IF NOT EXISTS member_cart_items (
            member_id INT NOT NULL,
            product_id INT NOT NULL,
            quantity INT NOT NULL DEFAULT 1,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (member_id, product_id),
            CONSTRAINT fk_member_cart_member FOREIGN KEY (member_id) REFERENCES members(member_id) ON DELETE CASCADE,
            CONSTRAINT fk_member_cart_product FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );
}

function merge_session_cart_to_member(mysqli $db, int $memberId, array $sessionCart): void {
    if (empty($sessionCart)) {
        return;
    }
    $stmt = $db->prepare(
        'INSERT INTO member_cart_items (member_id, product_id, quantity)
         VALUES (?, ?, ?)
         ON DUPLICATE KEY UPDATE quantity = quantity + VALUES(quantity), updated_at = CURRENT_TIMESTAMP'
    );
    foreach ($sessionCart as $item) {
        $productId = intval($item['product_id'] ?? 0);
        $quantity = intval($item['quantity'] ?? 0);
        if ($productId <= 0 || $quantity <= 0) {
            continue;
        }
        $stmt->bind_param('iii', $memberId, $productId, $quantity);
        $stmt->execute();
    }
}

function fetch_member_cart_items(mysqli $db, int $memberId): array {
    $sql = 'SELECT mci.product_id, mci.quantity, p.name, p.price, p.image_url
            FROM member_cart_items mci
            JOIN products p ON p.product_id = mci.product_id
            WHERE mci.member_id = ? AND p.is_active = 1';
    $stmt = $db->prepare($sql);
    $stmt->bind_param('i', $memberId);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    $cart = [];
    foreach ($rows as $row) {
        $pid = intval($row['product_id']);
        $qty = intval($row['quantity']);
        if ($pid <= 0 || $qty <= 0) {
            continue;
        }
        $cart[$pid] = [
            'product_id' => $pid,
            'name' => $row['name'],
            'price' => $row['price'],
            'quantity' => $qty,
            'image_url' => $row['image_url']
        ];
    }
    return $cart;
}

function save_member_cart_item(int $memberId, int $productId, int $quantity): void {
    $db = get_db();
    ensure_member_cart_table($db);
    $stmt = $db->prepare(
        'INSERT INTO member_cart_items (member_id, product_id, quantity)
         VALUES (?, ?, ?)
         ON DUPLICATE KEY UPDATE quantity = VALUES(quantity), updated_at = CURRENT_TIMESTAMP'
    );
    $stmt->bind_param('iii', $memberId, $productId, $quantity);
    $stmt->execute();
}

function delete_member_cart_item(int $memberId, int $productId): void {
    $db = get_db();
    ensure_member_cart_table($db);
    $stmt = $db->prepare('DELETE FROM member_cart_items WHERE member_id = ? AND product_id = ?');
    $stmt->bind_param('ii', $memberId, $productId);
    $stmt->execute();
}

function clear_member_cart(int $memberId): void {
    $db = get_db();
    ensure_member_cart_table($db);
    $stmt = $db->prepare('DELETE FROM member_cart_items WHERE member_id = ?');
    $stmt->bind_param('i', $memberId);
    $stmt->execute();
}

function current_member_id(): int {
    $user = current_user();
    if (!$user || empty($user['member_id'])) {
        return 0;
    }
    return intval($user['member_id']);
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
    $stmt = $db->prepare('SELECT product_id, name, price, image_url FROM products WHERE product_id = ? AND is_active = 1');
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
            'image_url' => $product['image_url']
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

    $sql = "SELECT oi.product_id, oi.quantity, oi.$priceColumn AS price, p.name, p.image_url
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
            'image_url' => $item['image_url']
        ];
        save_member_cart_item($memberId, $pid, $qty);
    }

    respond_json(['success' => true]);
}
