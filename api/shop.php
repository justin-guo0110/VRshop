<?php
require_once __DIR__ . '/db.php';

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'featured_products':
        featured_products();
        break;
    case 'sidebar_ads':
        sidebar_ads();
        break;
    case 'search_products':
        search_products();
        break;
    case 'get_product':
        get_product();
        break;
    case 'list_products':
        require_admin();
        list_products();
        break;
    case 'update_product':
        require_admin();
        update_product();
        break;
    case 'delete_product':
        require_admin();
        delete_product();
        break;
    default:
        respond_json(['error' => 'Unknown action'], 400);
}

function search_products(): void {
    $db = get_db();
    $keyword = trim($_GET['keyword'] ?? '');
    $category = trim($_GET['category'] ?? '');
    $sort = trim($_GET['sort'] ?? 'newest');
    $min_price = floatval($_GET['min_price'] ?? 0);
    $max_price = floatval($_GET['max_price'] ?? PHP_FLOAT_MAX);
    $in_stock = intval($_GET['in_stock'] ?? 0);
    $page = max(1, intval($_GET['page'] ?? 1));
    $page_size = max(1, intval($_GET['page_size'] ?? 12));

    $sql = 'SELECT 
                product_id,
                name,
                category,
                description,
                price,
                stock,
                image_url,
                is_active,
                COALESCE((
                    SELECT SUM(oi.quantity)
                    FROM order_items oi
                    JOIN orders o ON o.order_id = oi.order_id
                    WHERE oi.product_id = products.product_id
                      AND o.status IN ("pending", "accepted", "preparing", "shipping", "done")
                ), 0) AS sold_count,
                CASE
                    WHEN product_id >= (
                        SELECT COALESCE(MAX(p2.product_id), 0) - 6
                        FROM products p2
                        WHERE p2.is_active = 1
                    ) THEN 1
                    ELSE 0
                END AS is_new,
                CASE
                    WHEN name LIKE "%折%" OR name LIKE "%特價%" OR name LIKE "%限時%"
                      OR description LIKE "%折%" OR description LIKE "%特價%" OR description LIKE "%限時%"
                    THEN 1
                    ELSE 0
                END AS is_limited_offer
            FROM products
            WHERE is_active = 1';
    $params = [];
    $types = '';
    
    if ($keyword !== '') {
        $sql .= ' AND (name LIKE ? OR description LIKE ?)';
        $kw = '%' . $keyword . '%';
        $params[] = $kw;
        $params[] = $kw;
        $types .= 'ss';
    }
    if ($category !== '') {
        $sql .= ' AND category = ?';
        $params[] = $category;
        $types .= 's';
    }
    if ($min_price > 0) {
        $sql .= ' AND price >= ?';
        $params[] = $min_price;
        $types .= 'd';
    }
    if ($max_price < PHP_FLOAT_MAX) {
        $sql .= ' AND price <= ?';
        $params[] = $max_price;
        $types .= 'd';
    }
    if ($in_stock) {
        $sql .= ' AND stock > 0';
    }
    
    // Count total
    $count_sql = 'SELECT COUNT(*) as cnt FROM products WHERE is_active = 1';
    if ($keyword !== '') {
        $count_sql .= ' AND (name LIKE ? OR description LIKE ?)';
    }
    if ($category !== '') {
        $count_sql .= ' AND category = ?';
    }
    if ($min_price > 0) {
        $count_sql .= ' AND price >= ?';
    }
    if ($max_price < PHP_FLOAT_MAX) {
        $count_sql .= ' AND price <= ?';
    }
    if ($in_stock) {
        $count_sql .= ' AND stock > 0';
    }
    $count_stmt = $db->prepare($count_sql);
    if (!empty($params)) {
        $count_stmt->bind_param($types, ...$params);
    }
    $count_stmt->execute();
    $count_res = $count_stmt->get_result();
    $total = $count_res->fetch_assoc()['cnt'] ?? 0;
    
    // Sort
    $sort_map = [
        'newest' => 'product_id DESC',
        'price_asc' => 'price ASC',
        'price_desc' => 'price DESC',
        'name_asc' => 'name ASC'
    ];
    $order = $sort_map[$sort] ?? 'product_id DESC';
    $sql .= ' ORDER BY ' . $order;
    
    // Pagination
    $offset = ($page - 1) * $page_size;
    $sql .= ' LIMIT ?, ?';
    $params[] = $offset;
    $params[] = $page_size;
    $types .= 'ii';
    
    $stmt = $db->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $res = $stmt->get_result();
    $products = $res->fetch_all(MYSQLI_ASSOC);
    respond_json(['products' => $products, 'total' => $total, 'page' => $page, 'page_size' => $page_size]);
}

function default_featured_products_config(): array {
    return [
        ['product_id' => 0, 'badge' => '最多人購買'],
        ['product_id' => 0, 'badge' => '店長推薦'],
        ['product_id' => 0, 'badge' => '回購人氣王'],
        ['product_id' => 0, 'badge' => '今日熱銷'],
        ['product_id' => 0, 'badge' => '高評價商品'],
        ['product_id' => 0, 'badge' => '限量精選'],
    ];
}

function normalize_featured_products_config(array $raw): array {
    $normalized = [];
    foreach ($raw as $index => $row) {
        if (!is_array($row)) continue;
        $productId = intval($row['product_id'] ?? 0);
        $badge = trim((string)($row['badge'] ?? ''));
        if ($badge === '') {
            $badge = default_featured_products_config()[$index]['badge'] ?? '精選推薦';
        }
        $normalized[] = [
            'product_id' => max(0, $productId),
            'badge' => substr($badge, 0, 24),
        ];
    }

    while (count($normalized) < 6) {
        $default = default_featured_products_config()[count($normalized)] ?? ['product_id' => 0, 'badge' => '精選推薦'];
        $normalized[] = $default;
    }

    if (count($normalized) > 6) {
        $normalized = array_slice($normalized, 0, 6);
    }

    return array_values($normalized);
}

function load_featured_products_config_from_file(): ?array {
    $path = __DIR__ . '/../storage/featured_products.json';
    if (!is_file($path)) {
        return null;
    }
    $raw = @file_get_contents($path);
    if ($raw === false || trim($raw) === '') {
        return null;
    }
    $decoded = json_decode($raw, true);
    if (!is_array($decoded)) {
        return null;
    }
    return normalize_featured_products_config($decoded);
}

function load_featured_products_config_from_db(mysqli $db): ?array {
    $stmt = $db->prepare('SELECT config_value FROM promotion_config WHERE config_type = ? AND config_key = ? AND is_active = 1 LIMIT 1');
    if (!$stmt) {
        return null;
    }
    $type = 'featured_products';
    $key = 'homepage';
    $stmt->bind_param('ss', $type, $key);
    if (!$stmt->execute()) {
        return null;
    }
    $res = $stmt->get_result();
    $row = $res ? $res->fetch_assoc() : null;
    if (!$row) {
        return null;
    }

    $decoded = json_decode((string)($row['config_value'] ?? ''), true);
    if (!is_array($decoded)) {
        return null;
    }

    return normalize_featured_products_config($decoded);
}

function featured_products(): void {
    $db = get_db();
    $config = load_featured_products_config_from_db($db);
    if (!is_array($config)) {
        $config = load_featured_products_config_from_file();
    }
    if (!is_array($config)) {
        $config = default_featured_products_config();
    }

    $map = [];
    foreach ($config as $idx => $row) {
        if (!is_array($row)) continue;
        $pid = intval($row['product_id'] ?? 0);
        if ($pid <= 0) continue;
        $badge = trim((string)($row['badge'] ?? ''));
        $map[$pid] = [
            'order' => intval($idx),
            'badge' => $badge !== '' ? $badge : '精選推薦',
        ];
    }

    $products = [];

    if (!empty($map)) {
        $ids = array_keys($map);
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $types = str_repeat('i', count($ids));
        $sql = "SELECT product_id, name, category, description, price, stock, image_url, is_active
                FROM products
                WHERE is_active = 1 AND product_id IN ($placeholders)";
        $stmt = $db->prepare($sql);
        $stmt->bind_param($types, ...$ids);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $pid = intval($row['product_id']);
            $row['featured_badge'] = $map[$pid]['badge'] ?? '精選推薦';
            $row['_order'] = $map[$pid]['order'] ?? 999;
            $products[] = $row;
        }

        usort($products, function ($a, $b) {
            return ($a['_order'] ?? 999) <=> ($b['_order'] ?? 999);
        });
        foreach ($products as &$p) {
            unset($p['_order']);
        }
        unset($p);
    }

    if (count($products) < 6) {
        $need = 6 - count($products);
        $excludeIds = array_map(fn($p) => intval($p['product_id']), $products);
        $extraSql = 'SELECT product_id, name, category, description, price, stock, image_url, is_active
                     FROM products
                     WHERE is_active = 1';
        if (!empty($excludeIds)) {
            $extraSql .= ' AND product_id NOT IN (' . implode(',', array_fill(0, count($excludeIds), '?')) . ')';
        }
        $extraSql .= ' ORDER BY product_id DESC LIMIT ?';

        $stmt = $db->prepare($extraSql);
        if (!empty($excludeIds)) {
            $types = str_repeat('i', count($excludeIds)) . 'i';
            $params = array_merge($excludeIds, [$need]);
            $stmt->bind_param($types, ...$params);
        } else {
            $stmt->bind_param('i', $need);
        }
        $stmt->execute();
        $res = $stmt->get_result();
        $fallbackLabels = ['最多人購買', '店長推薦', '回購人氣王', '今日熱銷', '高評價商品', '限量精選'];
        while ($row = $res->fetch_assoc()) {
            $row['featured_badge'] = $fallbackLabels[count($products)] ?? '精選推薦';
            $products[] = $row;
        }
    }

    respond_json(['products' => array_slice($products, 0, 6)]);
}

function default_sidebar_ads_config(): array {
    return [
        ['image_url' => '', 'link_url' => './products.php', 'alt' => '側邊廣告 1'],
        ['image_url' => '', 'link_url' => './products.php', 'alt' => '側邊廣告 2'],
        ['image_url' => '', 'link_url' => './products.php', 'alt' => '側邊廣告 3'],
        ['image_url' => '', 'link_url' => './products.php', 'alt' => '側邊廣告 4'],
    ];
}

function normalize_sidebar_ads_config(array $raw): array {
    $normalized = [];
    foreach ($raw as $index => $row) {
        if (!is_array($row)) continue;
        $imageUrl = trim((string)($row['image_url'] ?? ''));
        $linkUrl = trim((string)($row['link_url'] ?? ''));
        $alt = trim((string)($row['alt'] ?? ''));

        if ($imageUrl === '') {
            $imageUrl = default_sidebar_ads_config()[$index]['image_url'] ?? '';
        }
        if ($linkUrl === '') {
            $linkUrl = './products.php';
        }
        if ($alt === '') {
            $alt = '側邊廣告 ' . ($index + 1);
        }

        $normalized[] = [
            'image_url' => substr($imageUrl, 0, 500),
            'link_url' => substr($linkUrl, 0, 500),
            'alt' => substr($alt, 0, 120),
        ];
    }

    while (count($normalized) < 4) {
        $normalized[] = default_sidebar_ads_config()[count($normalized)] ?? ['image_url' => '', 'link_url' => './products.php', 'alt' => '側邊廣告'];
    }

    if (count($normalized) > 4) {
        $normalized = array_slice($normalized, 0, 4);
    }

    return array_values($normalized);
}

function load_sidebar_ads_config_from_db(mysqli $db): ?array {
    $type = 'sidebar_ads';
    $key = 'homepage';
    $stmt = $db->prepare('SELECT config_value FROM promotion_config WHERE config_type = ? AND config_key = ? AND is_active = 1 LIMIT 1');
    if (!$stmt) {
        return null;
    }
    $stmt->bind_param('ss', $type, $key);
    if (!$stmt->execute()) {
        return null;
    }

    $res = $stmt->get_result();
    $row = $res ? $res->fetch_assoc() : null;
    if (!$row) {
        return null;
    }

    $decoded = json_decode((string)($row['config_value'] ?? ''), true);
    if (!is_array($decoded)) {
        return null;
    }

    return normalize_sidebar_ads_config($decoded);
}

function load_sidebar_ads_config_from_file(): ?array {
    $path = __DIR__ . '/../storage/sidebar_ads.json';
    if (!is_file($path)) {
        return null;
    }
    $content = @file_get_contents($path);
    if ($content === false || trim($content) === '') {
        return null;
    }
    $decoded = json_decode($content, true);
    if (!is_array($decoded)) {
        return null;
    }

    return normalize_sidebar_ads_config($decoded);
}

function sidebar_ads(): void {
    $db = get_db();
    $config = load_sidebar_ads_config_from_db($db);
    if (!is_array($config)) {
        $config = load_sidebar_ads_config_from_file();
    }
    if (!is_array($config)) {
        $config = default_sidebar_ads_config();
    }

    respond_json(['success' => true, 'sidebar_ads' => $config]);
}

function get_product(): void {
    $db = get_db();
    $product_id = intval($_GET['product_id'] ?? 0);
    if (!$product_id) {
        respond_json(['error' => 'Product id required'], 422);
    }
    $stmt = $db->prepare('SELECT product_id, name, category, description, price, stock, image_url, is_active FROM products WHERE product_id = ?');
    $stmt->bind_param('i', $product_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $product = $res->fetch_assoc();
    if (!$product || !$product['is_active']) {
        respond_json(['error' => 'Product not found'], 404);
    }
    respond_json(['product' => $product]);
}

// 管理員專用：列出所有商品（包括已下架）
function list_products(): void {
    $db = get_db();
    $stmt = $db->prepare('SELECT product_id, name, category, description, price, stock, image_url, is_active FROM products ORDER BY product_id DESC');
    $stmt->execute();
    $res = $stmt->get_result();
    $products = $res->fetch_all(MYSQLI_ASSOC);
    respond_json(['products' => $products]);
}

function update_product(): void {
    $db = get_db();
    $product_id = intval($_POST['product_id'] ?? 0);
    if (!$product_id) {
        respond_json(['error' => 'Product id required'], 422);
        return;
    }
    
    $name = trim($_POST['name'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $price = floatval($_POST['price'] ?? 0);
    $stock = intval($_POST['stock'] ?? 0);
    $image_url = trim($_POST['image_url'] ?? '');
    $is_active = intval($_POST['is_active'] ?? 1);
    
    if (!$name || $price < 0 || $stock < 0) {
        respond_json(['error' => '商品名稱、價格和庫存為必填項'], 422);
        return;
    }
    
    $stmt = $db->prepare('UPDATE products SET name=?, category=?, price=?, stock=?, image_url=?, is_active=? WHERE product_id=?');
    $stmt->bind_param('ssdisii', $name, $category, $price, $stock, $image_url, $is_active, $product_id);
    
    if ($stmt->execute()) {
        respond_json(['success' => true, 'message' => '商品已更新']);
    } else {
        respond_json(['error' => '更新失敗'], 500);
    }
}

function delete_product(): void {
    $db = get_db();
    $product_id = intval($_POST['product_id'] ?? 0);
    if (!$product_id) {
        respond_json(['error' => 'Product id required'], 422);
        return;
    }
    
    $stmt = $db->prepare('DELETE FROM products WHERE product_id=?');
    $stmt->bind_param('i', $product_id);
    
    if ($stmt->execute()) {
        respond_json(['success' => true, 'message' => '商品已刪除']);
    } else {
        respond_json(['error' => '刪除失敗'], 500);
    }
}
