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
    $sql .= ' ORDER BY product_id DESC';
    $stmt = $db->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $res = $stmt->get_result();
    $products = $res->fetch_all(MYSQLI_ASSOC);
    respond_json(['products' => $products]);
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
