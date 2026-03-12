<?php
require_once __DIR__ . '/db.php';

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'search_products':
        search_products();
        break;
    case 'get_product':
        get_product();
        break;
    default:
        respond_json(['error' => 'Unknown action'], 400);
}

function search_products(): void {
    $db = get_db();
    $keyword = trim($_GET['keyword'] ?? '');
    $category = trim($_GET['category'] ?? '');
    $min_price = isset($_GET['min_price']) ? floatval($_GET['min_price']) : null;
    $max_price = isset($_GET['max_price']) ? floatval($_GET['max_price']) : null;
    $in_stock = isset($_GET['in_stock']) ? intval($_GET['in_stock']) : 0;
    $sort = trim($_GET['sort'] ?? 'newest');
    $page = max(1, intval($_GET['page'] ?? 1));
    $page_size = intval($_GET['page_size'] ?? 12);
    if ($page_size <= 0) $page_size = 12;
    if ($page_size > 60) $page_size = 60;

    $where = ' WHERE is_active = 1';
    $params = [];
    $types = '';
    if ($keyword !== '') {
        $where .= ' AND (name LIKE ? OR description LIKE ?)';
        $kw = '%' . $keyword . '%';
        $params[] = $kw;
        $params[] = $kw;
        $types .= 'ss';
    }
    if ($category !== '') {
        $where .= ' AND category = ?';
        $params[] = $category;
        $types .= 's';
    }
    if ($min_price !== null && $min_price >= 0) {
        $where .= ' AND price >= ?';
        $params[] = $min_price;
        $types .= 'd';
    }
    if ($max_price !== null && $max_price >= 0) {
        $where .= ' AND price <= ?';
        $params[] = $max_price;
        $types .= 'd';
    }
    if ($in_stock === 1) {
        $where .= ' AND stock > 0';
    }

    $order_by = 'product_id DESC';
    switch ($sort) {
        case 'price_asc':
            $order_by = 'price ASC, product_id DESC';
            break;
        case 'price_desc':
            $order_by = 'price DESC, product_id DESC';
            break;
        case 'name_asc':
            $order_by = 'name ASC, product_id DESC';
            break;
        case 'newest':
        default:
            $order_by = 'product_id DESC';
    }

    $count_sql = 'SELECT COUNT(*) AS total FROM products' . $where;
    $count_stmt = $db->prepare($count_sql);
    if (!empty($params)) {
        $count_stmt->bind_param($types, ...$params);
    }
    $count_stmt->execute();
    $total = intval(($count_stmt->get_result()->fetch_assoc()['total'] ?? 0));

    $offset = ($page - 1) * $page_size;
    $sql = 'SELECT product_id, name, category, description, price, stock, image_url, is_active FROM products'
        . $where
        . ' ORDER BY ' . $order_by
        . ' LIMIT ? OFFSET ?';
    $stmt = $db->prepare($sql);
    $bind_params = $params;
    $bind_types = $types . 'ii';
    $bind_params[] = $page_size;
    $bind_params[] = $offset;
    $stmt->bind_param($bind_types, ...$bind_params);
    $stmt->execute();
    $res = $stmt->get_result();
    $products = $res->fetch_all(MYSQLI_ASSOC);

    respond_json([
        'products' => $products,
        'total' => $total,
        'page' => $page,
        'page_size' => $page_size
    ]);
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
