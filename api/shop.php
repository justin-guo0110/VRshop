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
    $sort = trim($_GET['sort'] ?? 'newest');
    $min_price = floatval($_GET['min_price'] ?? 0);
    $max_price = floatval($_GET['max_price'] ?? PHP_FLOAT_MAX);
    $in_stock = intval($_GET['in_stock'] ?? 0);
    $page = max(1, intval($_GET['page'] ?? 1));
    $page_size = max(1, intval($_GET['page_size'] ?? 12));

    $sql = 'SELECT product_id, name, category, description, price, stock, image_url, is_active FROM products WHERE is_active = 1';
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
    $count_sql = str_replace('SELECT product_id, name, category, description, price, stock, image_url, is_active', 'SELECT COUNT(*) as cnt', $sql);
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
