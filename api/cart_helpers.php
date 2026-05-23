<?php

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
    $sql = 'SELECT mci.product_id, mci.quantity, p.name, p.price, p.image_url, p.category
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
            'image_url' => $row['image_url'],
            'category' => $row['category'] ?? ''
        ];
    }

    return $cart;
}

function hydrate_cart_snapshot(mysqli $db, array $cart): array {
    if (empty($cart)) {
        return [];
    }

    $productIds = [];
    foreach ($cart as $item) {
        $pid = intval($item['product_id'] ?? 0);
        if ($pid > 0) {
            $productIds[] = $pid;
        }
    }
    $productIds = array_values(array_unique($productIds));
    if (empty($productIds)) {
        return [];
    }

    $placeholders = implode(',', array_fill(0, count($productIds), '?'));
    $types = str_repeat('i', count($productIds));
    $stmt = $db->prepare("SELECT product_id, name, price, image_url, category, stock, is_active FROM products WHERE product_id IN ($placeholders)");
    $stmt->bind_param($types, ...$productIds);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    $products = [];
    foreach ($rows as $row) {
        $products[intval($row['product_id'])] = $row;
    }

    $hydrated = [];
    foreach ($cart as $item) {
        $pid = intval($item['product_id'] ?? 0);
        $qty = max(0, intval($item['quantity'] ?? 0));
        if ($pid <= 0 || $qty <= 0 || !isset($products[$pid]) || intval($products[$pid]['is_active'] ?? 0) !== 1) {
            continue;
        }
        $product = $products[$pid];
        $hydrated[$pid] = [
            'product_id' => $pid,
            'name' => $product['name'],
            'price' => $product['price'],
            'quantity' => $qty,
            'image_url' => $product['image_url'],
            'category' => $product['category'] ?? '',
            'stock' => intval($product['stock'] ?? 0)
        ];
    }

    return $hydrated;
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