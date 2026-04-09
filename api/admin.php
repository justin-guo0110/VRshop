<?php
require_once __DIR__ . '/db.php';
$user = require_admin();

$action = $_GET['action'] ?? '';
switch ($action) {
    case 'list_orders':
        list_orders();
        break;
    case 'list_refund_requests':
        list_refund_requests();
        break;
    case 'review_refund_request':
        review_refund_request($user);
        break;
    case 'update_order_status':
        update_order_status();
        break;
    case 'delete_order':
        delete_order();
        break;
    case 'list_products':
        list_products();
        break;
    case 'update_product_status':
        update_product_status();
        break;
    case 'update_product':
        update_product();
        break;
    case 'create_product':
        create_product();
        break;
    case 'delete_product':
        delete_product();
        break;
    case 'dashboard_stats':
        dashboard_stats();
        break;
    case 'receiving_products':
        receiving_products();
        break;
    case 'receiving_create':
        receiving_create();
        break;
    case 'receiving_list':
        receiving_list();
        break;
    case 'receiving_detail':
        receiving_detail();
        break;
    case 'stock_movements_list':
        stock_movements_list();
        break;
    case 'stock_adjust':
        stock_adjust();
        break;
    case 'get_all_chats':
        get_all_chats();
        break;
    case 'get_chat_history':
        get_chat_history();
        break;
    case 'reply_chat':
        reply_chat();
        break;
    case 'get_wheel_prizes':
        get_wheel_prizes_config();
        break;
    case 'save_wheel_prizes':
        save_wheel_prizes_config();
        break;
    case 'get_featured_products':
        get_featured_products_config();
        break;
    case 'save_featured_products':
        save_featured_products_config();
        break;
    case 'get_sidebar_ads':
        get_sidebar_ads_config();
        break;
    case 'save_sidebar_ads':
        save_sidebar_ads_config();
        break;
    case 'upload_sidebar_ad_image':
        upload_sidebar_ad_image();
        break;
    case 'list_customers':
        list_customers();
        break;
    case 'list_promotions':
        list_promotions();
        break;
    case 'save_promotion':
        save_promotion($user);
        break;
    case 'toggle_promotion_status':
        toggle_promotion_status();
        break;
    default:
        respond_json(['error' => 'Unknown action'], 400);
}

function list_customers(): void {
    $db = get_db();

    $sql = "SELECT m.member_id, m.name, m.email,
                   COUNT(o.order_id) AS order_count,
                   COALESCE(SUM(o.total_amount), 0) AS total_spent,
                   MAX(o.created_at) AS last_order_at
            FROM members m
            LEFT JOIN orders o ON o.member_id = m.member_id
            WHERE m.role = 'member'
            GROUP BY m.member_id, m.name, m.email
            ORDER BY m.member_id DESC";

    $res = $db->query($sql);
    if (!$res) {
        respond_json(['error' => 'Load customers failed'], 500);
    }

    $customers = [];
    while ($row = $res->fetch_assoc()) {
        $orderCount = intval($row['order_count'] ?? 0);
        $totalSpent = floatval($row['total_spent'] ?? 0);

        $labels = [];
        if ($totalSpent >= 5000) {
            $labels[] = 'VIP';
        }
        if ($orderCount >= 5) {
            $labels[] = '常購';
        }
        if ($orderCount === 0) {
            $labels[] = '新客';
        }

        $row['order_count'] = $orderCount;
        $row['total_spent'] = $totalSpent;
        $row['labels'] = $labels;
        $customers[] = $row;
    }

    respond_json(['customers' => $customers]);
}

function list_promotions(): void {
    $db = get_db();
    if (!table_exists($db, 'promotions')) {
        respond_json(['success' => true, 'promotions' => [], 'message' => 'promotions table not found']);
    }

    $sql = 'SELECT promotion_id, title, description, promotion_type, discount_type, discount_value, min_amount, max_discount,
                   product_id, start_date, end_date, is_active, created_by, created_at
            FROM promotions
            ORDER BY created_at DESC, promotion_id DESC';
    $res = $db->query($sql);
    if (!$res) {
        respond_json(['error' => 'Load promotions failed'], 500);
    }
    $promotions = $res->fetch_all(MYSQLI_ASSOC);
    respond_json(['success' => true, 'promotions' => $promotions]);
}

function normalize_datetime_input(string $value): ?string {
    $value = trim($value);
    if ($value === '') return null;
    // Convert datetime-local format to MySQL datetime.
    $value = str_replace('T', ' ', $value);
    if (strlen($value) === 16) {
        $value .= ':00';
    }
    $ts = strtotime($value);
    if ($ts === false) return null;
    return date('Y-m-d H:i:s', $ts);
}

function save_promotion(array $adminUser): void {
    $db = get_db();
    if (!table_exists($db, 'promotions')) {
        respond_json(['error' => 'promotions table not found'], 500);
    }

    $promotionId = intval($_POST['promotion_id'] ?? 0);
    $title = trim((string)($_POST['title'] ?? ''));
    $description = trim((string)($_POST['description'] ?? ''));
    $promotionType = trim((string)($_POST['promotion_type'] ?? 'global_discount'));
    $discountType = trim((string)($_POST['discount_type'] ?? 'percent'));
    $discountValue = floatval($_POST['discount_value'] ?? 0);
    $minAmount = floatval($_POST['min_amount'] ?? 0);
    $maxDiscountRaw = trim((string)($_POST['max_discount'] ?? ''));
    $productIdRaw = trim((string)($_POST['product_id'] ?? ''));
    $startDate = normalize_datetime_input((string)($_POST['start_date'] ?? ''));
    $endDate = normalize_datetime_input((string)($_POST['end_date'] ?? ''));

    $allowedPromotionTypes = ['global_discount', 'threshold_discount', 'add_on_purchase', 'bundle', 'free_shipping', 'coupon'];
    $allowedDiscountTypes = ['percent', 'fixed'];

    if ($title === '' || !$startDate || !$endDate) {
        respond_json(['error' => '請填寫標題、開始與結束時間'], 422);
    }
    if (!in_array($promotionType, $allowedPromotionTypes, true)) {
        respond_json(['error' => '無效的促銷類型'], 422);
    }
    if (!in_array($discountType, $allowedDiscountTypes, true)) {
        respond_json(['error' => '無效的折扣類型'], 422);
    }
    if ($discountValue < 0) {
        respond_json(['error' => '折扣值不可小於 0'], 422);
    }
    if ($discountType === 'percent' && $discountValue > 100) {
        respond_json(['error' => '百分比折扣不可大於 100'], 422);
    }
    if (strtotime($endDate) < strtotime($startDate)) {
        respond_json(['error' => '結束時間不可早於開始時間'], 422);
    }

    $maxDiscount = $maxDiscountRaw === '' ? null : floatval($maxDiscountRaw);
    if ($maxDiscount !== null && $maxDiscount < 0) {
        respond_json(['error' => '最大折扣不可小於 0'], 422);
    }
    $productId = $productIdRaw === '' ? null : intval($productIdRaw);
    if ($productId !== null && $productId <= 0) {
        respond_json(['error' => '指定商品 ID 無效'], 422);
    }

    if ($promotionId > 0) {
        $sql = 'UPDATE promotions
                SET title = ?, description = ?, promotion_type = ?, discount_type = ?, discount_value = ?, min_amount = ?,
                    max_discount = ?, product_id = ?, start_date = ?, end_date = ?
                WHERE promotion_id = ?';
        $stmt = $db->prepare($sql);
        $stmt->bind_param(
            'ssssdddissi',
            $title,
            $description,
            $promotionType,
            $discountType,
            $discountValue,
            $minAmount,
            $maxDiscount,
            $productId,
            $startDate,
            $endDate,
            $promotionId
        );
        if (!$stmt->execute()) {
            respond_json(['error' => '更新促銷失敗'], 500);
        }
        respond_json(['success' => true, 'message' => '促銷已更新']);
    }

    $createdBy = intval($adminUser['member_id'] ?? 0) ?: null;
    $sql = 'INSERT INTO promotions (title, description, promotion_type, discount_type, discount_value, min_amount, max_discount, product_id, start_date, end_date, is_active, created_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?)';
    $stmt = $db->prepare($sql);
    $stmt->bind_param(
        'ssssdddissi',
        $title,
        $description,
        $promotionType,
        $discountType,
        $discountValue,
        $minAmount,
        $maxDiscount,
        $productId,
        $startDate,
        $endDate,
        $createdBy
    );
    if (!$stmt->execute()) {
        respond_json(['error' => '新增促銷失敗'], 500);
    }
    respond_json(['success' => true, 'message' => '促銷已建立', 'promotion_id' => $stmt->insert_id]);
}

function toggle_promotion_status(): void {
    $db = get_db();
    if (!table_exists($db, 'promotions')) {
        respond_json(['error' => 'promotions table not found'], 500);
    }

    $promotionId = intval($_POST['promotion_id'] ?? 0);
    $isActive = intval($_POST['is_active'] ?? -1);
    if ($promotionId <= 0 || !in_array($isActive, [0, 1], true)) {
        respond_json(['error' => 'Invalid data'], 422);
    }

    $stmt = $db->prepare('UPDATE promotions SET is_active = ? WHERE promotion_id = ?');
    $stmt->bind_param('ii', $isActive, $promotionId);
    if (!$stmt->execute()) {
        respond_json(['error' => '更新狀態失敗'], 500);
    }
    respond_json(['success' => true]);
}

function wheel_prizes_file_path(): string {
    return __DIR__ . '/../storage/lucky_wheel_prizes.json';
}

function default_wheel_prizes(): array {
    return [
        ['id' => 0, 'name' => '折扣10%', 'discount_type' => 'percent', 'value' => 10, 'min_purchase' => 0],
        ['id' => 1, 'name' => '折扣15%', 'discount_type' => 'percent', 'value' => 15, 'min_purchase' => 100],
        ['id' => 2, 'name' => '折扣20%', 'discount_type' => 'percent', 'value' => 20, 'min_purchase' => 200],
        ['id' => 3, 'name' => '折扣$30', 'discount_type' => 'fixed', 'value' => 30, 'min_purchase' => 200],
        ['id' => 4, 'name' => '免運費', 'discount_type' => 'fixed', 'value' => 50, 'min_purchase' => 500],
        ['id' => 5, 'name' => '折扣$50', 'discount_type' => 'fixed', 'value' => 50, 'min_purchase' => 300],
        ['id' => 6, 'name' => '折扣$20', 'discount_type' => 'fixed', 'value' => 20, 'min_purchase' => 100],
        ['id' => 7, 'name' => '立減$100', 'discount_type' => 'fixed', 'value' => 100, 'min_purchase' => 500],
    ];
}

function normalize_wheel_prizes(array $raw): array {
    $normalized = [];
    foreach ($raw as $index => $row) {
        if (!is_array($row)) continue;
        $type = ($row['discount_type'] ?? 'fixed') === 'percent' ? 'percent' : 'fixed';
        $name = trim((string)($row['name'] ?? ''));
        if ($name === '') {
            $name = $type === 'percent' ? '折扣10%' : '折扣$10';
        }
        $value = max(0, floatval($row['value'] ?? 0));
        $minPurchase = max(0, floatval($row['min_purchase'] ?? 0));
        $normalized[] = [
            'id' => intval($row['id'] ?? $index),
            'name' => $name,
            'discount_type' => $type,
            'value' => $value,
            'min_purchase' => $minPurchase,
        ];
    }

    if (count($normalized) !== 8) {
        return default_wheel_prizes();
    }
    return array_values($normalized);
}

function load_wheel_prizes_config_array(): array {
    $path = wheel_prizes_file_path();
    if (!is_file($path)) {
        return default_wheel_prizes();
    }

    $content = @file_get_contents($path);
    if ($content === false || trim($content) === '') {
        return default_wheel_prizes();
    }

    $decoded = json_decode($content, true);
    if (!is_array($decoded)) {
        return default_wheel_prizes();
    }

    return normalize_wheel_prizes($decoded);
}

function get_wheel_prizes_config(): void {
    respond_json(['prizes' => load_wheel_prizes_config_array()]);
}

function save_wheel_prizes_config(): void {
    $rawJson = $_POST['prizes_json'] ?? '';
    if (!is_string($rawJson) || trim($rawJson) === '') {
        respond_json(['error' => 'Missing prizes_json'], 422);
    }

    $decoded = json_decode($rawJson, true);
    if (!is_array($decoded)) {
        respond_json(['error' => 'Invalid prizes_json'], 422);
    }

    $normalized = normalize_wheel_prizes($decoded);
    if (count($normalized) !== 8) {
        respond_json(['error' => 'Prize config must contain 8 items'], 422);
    }

    $path = wheel_prizes_file_path();
    $written = @file_put_contents(
        $path,
        json_encode($normalized, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
        LOCK_EX
    );

    if ($written === false) {
        respond_json(['error' => 'Failed to save config'], 500);
    }

    respond_json(['success' => true, 'prizes' => $normalized]);
}

function featured_products_file_path(): string {
    return __DIR__ . '/../storage/featured_products.json';
}

function featured_products_config_type(): string {
    return 'featured_products';
}

function featured_products_config_key(): string {
    return 'homepage';
}

function ensure_promotion_config_table(mysqli $db): bool {
    if (table_exists($db, 'promotion_config')) {
        return true;
    }

    $sql = 'CREATE TABLE IF NOT EXISTS promotion_config (
                config_id INT NOT NULL AUTO_INCREMENT,
                config_type VARCHAR(50) NOT NULL,
                config_key VARCHAR(100) NOT NULL,
                config_value LONGTEXT NOT NULL,
                description VARCHAR(255) DEFAULT NULL,
                is_active TINYINT(1) DEFAULT 1,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (config_id),
                UNIQUE KEY uk_config_type_key (config_type, config_key),
                KEY idx_config_active (is_active)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';

    return (bool)$db->query($sql);
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

function load_featured_products_config_from_db(mysqli $db): ?array {
    if (!table_exists($db, 'promotion_config')) {
        return null;
    }

    $type = featured_products_config_type();
    $key = featured_products_config_key();

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

    return normalize_featured_products_config($decoded);
}

function save_featured_products_config_to_db(mysqli $db, array $normalized): bool {
    if (!ensure_promotion_config_table($db)) {
        return false;
    }

    $payload = json_encode($normalized, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    if ($payload === false) {
        return false;
    }

    $type = featured_products_config_type();
    $key = featured_products_config_key();
    $description = '首頁熱門商品設定';
    $isActive = 1;

    // Keep SQL compatible with older schemas that may not have updated_by.
    $sql = 'INSERT INTO promotion_config (config_type, config_key, config_value, description, is_active)
            VALUES (?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                config_value = VALUES(config_value),
                description = VALUES(description),
                is_active = VALUES(is_active),
                updated_at = NOW()';

    $stmt = $db->prepare($sql);
    if (!$stmt) {
        return false;
    }
    $stmt->bind_param('ssssi', $type, $key, $payload, $description, $isActive);
    return $stmt->execute();
}

function save_featured_products_config_to_file(array $normalized): bool {
    $payload = json_encode($normalized, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    if ($payload === false) {
        return false;
    }

    $path = featured_products_file_path();
    $dir = dirname($path);
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }

    return @file_put_contents($path, $payload, LOCK_EX) !== false;
}

function load_featured_products_config_array(): array {
    $db = get_db();
    $fromDb = load_featured_products_config_from_db($db);
    if (is_array($fromDb)) {
        return $fromDb;
    }

    $path = featured_products_file_path();
    if (!is_file($path)) {
        return default_featured_products_config();
    }
    $content = @file_get_contents($path);
    if ($content === false || trim($content) === '') {
        return default_featured_products_config();
    }

    $decoded = json_decode($content, true);
    if (!is_array($decoded)) {
        return default_featured_products_config();
    }

    return normalize_featured_products_config($decoded);
}

function get_featured_products_config(): void {
    $config = load_featured_products_config_array();
    $db = get_db();
    $res = $db->query('SELECT product_id, name, category, price, is_active FROM products ORDER BY name ASC');
    $products = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
    respond_json(['success' => true, 'featured_products' => $config, 'products' => $products]);
}

function save_featured_products_config(): void {
    $rawJson = $_POST['featured_json'] ?? '';
    if (!is_string($rawJson) || trim($rawJson) === '') {
        respond_json(['error' => 'Missing featured_json'], 422);
    }

    $decoded = json_decode($rawJson, true);
    if (!is_array($decoded)) {
        respond_json(['error' => 'Invalid featured_json'], 422);
    }

    $normalized = normalize_featured_products_config($decoded);
    $db = get_db();
    $savedToDb = save_featured_products_config_to_db($db, $normalized);
    $savedToFile = save_featured_products_config_to_file($normalized);

    if (!$savedToDb && !$savedToFile) {
        $path = featured_products_file_path();
        $dir = dirname($path);
        $fileReason = is_dir($dir) ? 'storage not writable by web server' : 'storage directory missing';
        $dbReason = table_exists($db, 'promotion_config') ? 'db write failed' : 'promotion_config unavailable';
        respond_json([
            'error' => 'Write featured products config failed (DB/file unavailable)',
            'details' => [
                'db' => $dbReason,
                'file' => $fileReason,
                'path' => $path,
            ],
        ], 500);
    }

    respond_json([
        'success' => true,
        'featured_products' => $normalized,
        'saved_to_db' => $savedToDb,
        'saved_to_file' => $savedToFile,
    ]);
}

function sidebar_ads_file_path(): string {
    return __DIR__ . '/../storage/sidebar_ads.json';
}

function default_sidebar_ads_config(): array {
    return [
        [
            'image_url' => '',
            'link_url' => './products.php',
            'alt' => '側邊廣告 1',
        ],
        [
            'image_url' => '',
            'link_url' => './products.php',
            'alt' => '側邊廣告 2',
        ],
        [
            'image_url' => '',
            'link_url' => './products.php',
            'alt' => '側邊廣告 3',
        ],
        [
            'image_url' => '',
            'link_url' => './products.php',
            'alt' => '側邊廣告 4',
        ],
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
        $normalized[] = default_sidebar_ads_config()[count($normalized)] ?? [
            'image_url' => '',
            'link_url' => './products.php',
            'alt' => '側邊廣告',
        ];
    }

    if (count($normalized) > 4) {
        $normalized = array_slice($normalized, 0, 4);
    }

    return array_values($normalized);
}

function load_sidebar_ads_config_from_db(mysqli $db): ?array {
    if (!table_exists($db, 'promotion_config')) {
        return null;
    }

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

function save_sidebar_ads_config_to_db(mysqli $db, array $normalized): bool {
    if (!ensure_promotion_config_table($db)) {
        return false;
    }

    $payload = json_encode($normalized, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    if ($payload === false) {
        return false;
    }

    $type = 'sidebar_ads';
    $key = 'homepage';
    $description = '首頁側邊廣告圖片設定';
    $isActive = 1;
    $sql = 'INSERT INTO promotion_config (config_type, config_key, config_value, description, is_active)
            VALUES (?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                config_value = VALUES(config_value),
                description = VALUES(description),
                is_active = VALUES(is_active),
                updated_at = NOW()';
    $stmt = $db->prepare($sql);
    if (!$stmt) {
        return false;
    }
    $stmt->bind_param('ssssi', $type, $key, $payload, $description, $isActive);
    return $stmt->execute();
}

function save_sidebar_ads_config_to_file(array $normalized): bool {
    $payload = json_encode($normalized, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    if ($payload === false) {
        return false;
    }

    $path = sidebar_ads_file_path();
    $dir = dirname($path);
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }

    return @file_put_contents($path, $payload, LOCK_EX) !== false;
}

function load_sidebar_ads_config_array(): array {
    $db = get_db();
    $fromDb = load_sidebar_ads_config_from_db($db);
    if (is_array($fromDb)) {
        return $fromDb;
    }

    $path = sidebar_ads_file_path();
    if (!is_file($path)) {
        return default_sidebar_ads_config();
    }

    $content = @file_get_contents($path);
    if ($content === false || trim($content) === '') {
        return default_sidebar_ads_config();
    }

    $decoded = json_decode($content, true);
    if (!is_array($decoded)) {
        return default_sidebar_ads_config();
    }

    return normalize_sidebar_ads_config($decoded);
}

function get_sidebar_ads_config(): void {
    respond_json(['success' => true, 'sidebar_ads' => load_sidebar_ads_config_array()]);
}

function save_sidebar_ads_config(): void {
    $rawJson = $_POST['sidebar_json'] ?? '';
    if (!is_string($rawJson) || trim($rawJson) === '') {
        respond_json(['error' => 'Missing sidebar_json'], 422);
    }

    $decoded = json_decode($rawJson, true);
    if (!is_array($decoded)) {
        respond_json(['error' => 'Invalid sidebar_json'], 422);
    }

    $normalized = normalize_sidebar_ads_config($decoded);
    $db = get_db();
    $savedToDb = save_sidebar_ads_config_to_db($db, $normalized);
    $savedToFile = save_sidebar_ads_config_to_file($normalized);

    if (!$savedToDb && !$savedToFile) {
        respond_json([
            'error' => 'Write sidebar ads config failed (DB/file unavailable)',
        ], 500);
    }

    respond_json([
        'success' => true,
        'sidebar_ads' => $normalized,
        'saved_to_db' => $savedToDb,
        'saved_to_file' => $savedToFile,
    ]);
}

function upload_sidebar_ad_image(): void {
    if (!isset($_FILES['ad_image'])) {
        respond_json(['error' => 'Missing ad_image file'], 422);
    }

    $file = $_FILES['ad_image'];
    if (!is_array($file) || intval($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        respond_json(['error' => 'Upload failed'], 422);
    }

    $tmpPath = (string)($file['tmp_name'] ?? '');
    if ($tmpPath === '' || !is_file($tmpPath)) {
        respond_json(['error' => 'Invalid upload temp file'], 422);
    }

    $maxBytes = 5 * 1024 * 1024;
    if (intval($file['size'] ?? 0) > $maxBytes) {
        respond_json(['error' => 'Image too large (max 5MB)'], 422);
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = $finfo ? (string)finfo_file($finfo, $tmpPath) : '';
    if ($finfo) {
        finfo_close($finfo);
    }

    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
    ];
    if (!isset($allowed[$mime])) {
        respond_json(['error' => 'Unsupported image type'], 422);
    }

    $ext = $allowed[$mime];

    $targets = [
        [
            'dir' => __DIR__ . '/../public/uploads/sidebar_ads',
            'url_prefix' => '../public/uploads/sidebar_ads/'
        ],
        [
            'dir' => __DIR__ . '/../storage/sidebar_ads',
            'url_prefix' => '../storage/sidebar_ads/'
        ],
    ];

    $targetInfo = null;
    $reasons = [];
    foreach ($targets as $candidate) {
        $dir = (string)$candidate['dir'];
        if (!is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }
        if (!is_dir($dir)) {
            $reasons[] = $dir . ' not exists';
            continue;
        }
        if (!is_writable($dir)) {
            $reasons[] = $dir . ' not writable';
            continue;
        }
        $targetInfo = $candidate;
        break;
    }

    if (!$targetInfo) {
        respond_json([
            'error' => 'Create upload directory failed',
            'details' => $reasons,
        ], 500);
    }

    $filename = 'ad_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $dir = (string)$targetInfo['dir'];
    $target = $dir . '/' . $filename;
    if (!@move_uploaded_file($tmpPath, $target)) {
        respond_json(['error' => 'Save uploaded file failed'], 500);
    }
    @chmod($target, 0666);

    $imageUrl = (string)$targetInfo['url_prefix'] . $filename;
    respond_json([
        'success' => true,
        'image_url' => $imageUrl,
    ]);
}

function list_orders(): void {
    $db = get_db();
    $sql = 'SELECT o.order_id, o.member_id, o.address_id, o.total_amount, o.status, o.created_at, m.email, m.name
            FROM orders o
            JOIN members m ON o.member_id = m.member_id
            ORDER BY o.created_at DESC';
    $res = $db->query($sql);
    $orders = [];
    while ($row = $res->fetch_assoc()) {
        $row['items'] = [];
        $orders[$row['order_id']] = $row;
    }
    if (!empty($orders)) {
        $ids = implode(',', array_map('intval', array_keys($orders)));
        $itemsRes = $db->query("SELECT oi.order_id, oi.order_item_id, oi.product_id, oi.quantity, oi.unit_price, p.name FROM order_items oi JOIN products p ON oi.product_id = p.product_id WHERE oi.order_id IN ($ids)");
        while ($item = $itemsRes->fetch_assoc()) {
            $orders[$item['order_id']]['items'][] = $item;
        }
    }
    respond_json(['orders' => array_values($orders)]);
}

function list_refund_requests(): void {
    $db = get_db();
    ensure_refund_requests_table($db);

    $sql = 'SELECT rr.request_id, rr.order_id, rr.member_id, rr.reason, rr.note, rr.status, rr.review_note, rr.reviewed_by, rr.created_at, rr.reviewed_at,
                   o.status AS order_status, o.total_amount,
                   m.name AS member_name, m.email AS member_email,
                   admin.name AS reviewer_name
            FROM order_refund_requests rr
            JOIN orders o ON o.order_id = rr.order_id
            JOIN members m ON m.member_id = rr.member_id
            LEFT JOIN members admin ON admin.member_id = rr.reviewed_by
            ORDER BY rr.created_at DESC';

    $res = $db->query($sql);
    if (!$res) {
        respond_json(['error' => 'Load refund requests failed'], 500);
    }

    $requests = $res->fetch_all(MYSQLI_ASSOC);
    respond_json(['requests' => $requests]);
}

function review_refund_request(array $adminUser): void {
    $db = get_db();
    ensure_refund_requests_table($db);

    $requestId = intval($_POST['request_id'] ?? 0);
    $decision = trim((string)($_POST['decision'] ?? ''));
    $reviewNote = trim((string)($_POST['review_note'] ?? ''));

    if ($requestId <= 0 || !in_array($decision, ['approved', 'rejected'], true)) {
        respond_json(['error' => 'Invalid data'], 422);
    }

    $db->begin_transaction();
    try {
        // Lock and fetch the refund request with its order_id
        $lockStmt = $db->prepare('SELECT request_id, status, order_id FROM order_refund_requests WHERE request_id = ? FOR UPDATE');
        $lockStmt->bind_param('i', $requestId);
        $lockStmt->execute();
        $request = $lockStmt->get_result()->fetch_assoc();
        if (!$request) {
            throw new Exception('Request not found');
        }
        if (($request['status'] ?? '') !== 'pending') {
            throw new Exception('Request already reviewed');
        }

        $reviewedBy = intval($adminUser['member_id'] ?? 0);
        
        // Update the refund request status
        $updateStmt = $db->prepare('UPDATE order_refund_requests SET status = ?, review_note = ?, reviewed_by = ?, reviewed_at = NOW() WHERE request_id = ?');
        $updateStmt->bind_param('ssii', $decision, $reviewNote, $reviewedBy, $requestId);
        if (!$updateStmt->execute()) {
            throw new Exception('Review update failed');
        }

        // If decision is approved, mark order status when schema supports a cancel-like value.
        if ($decision === 'approved') {
            $orderId = intval($request['order_id']);
            $statusColumnRes = $db->query("SHOW COLUMNS FROM orders LIKE 'status'");
            $statusColumn = $statusColumnRes ? $statusColumnRes->fetch_assoc() : null;
            $statusType = strtolower((string)($statusColumn['Type'] ?? ''));

            $cancelStatus = null;
            if (strpos($statusType, "'cancelled'") !== false) {
                $cancelStatus = 'cancelled';
            } elseif (strpos($statusType, "'canceled'") !== false) {
                $cancelStatus = 'canceled';
            }

            if ($cancelStatus !== null) {
                $updateOrderStmt = $db->prepare('UPDATE orders SET status = ? WHERE order_id = ?');
                $updateOrderStmt->bind_param('si', $cancelStatus, $orderId);
                if (!$updateOrderStmt->execute()) {
                    throw new Exception('Order cancellation failed: ' . $updateOrderStmt->error);
                }
            }
        }

        $db->commit();
        respond_json(['success' => true]);
    } catch (Throwable $e) {
        $db->rollback();
        $msg = $e->getMessage();
        error_log('review_refund_request failed: ' . $msg);
        if ($msg === 'Request not found') {
            respond_json(['error' => '退單申請不存在'], 404);
        }
        if ($msg === 'Request already reviewed') {
            respond_json(['error' => '此申請已審核'], 422);
        }
        respond_json(['error' => '審核失敗'], 500);
    }
}

function table_exists(mysqli $db, string $table): bool {
    $tableEsc = $db->real_escape_string($table);
    $res = $db->query("SHOW TABLES LIKE '$tableEsc'");
    return $res && $res->num_rows > 0;
}

function ensure_refund_requests_table(mysqli $db): void {
    if (table_exists($db, 'order_refund_requests')) {
        return;
    }

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
            CONSTRAINT fk_refund_order_admin FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE CASCADE,
            CONSTRAINT fk_refund_member_admin FOREIGN KEY (member_id) REFERENCES members(member_id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );
}

function update_order_status(): void {
    $db = get_db();
    $order_id = intval($_POST['order_id'] ?? 0);
    $status = $_POST['status'] ?? '';
    $allowed = ['pending', 'preparing', 'shipping', 'done'];
    if (!$order_id || !in_array($status, $allowed, true)) {
        respond_json(['error' => 'Invalid data'], 422);
    }
    $db->begin_transaction();
    $currentStmt = $db->prepare('SELECT status FROM orders WHERE order_id = ? FOR UPDATE');
    $currentStmt->bind_param('i', $order_id);
    $currentStmt->execute();
    $currentRes = $currentStmt->get_result();
    $current = $currentRes->fetch_assoc();
    if (!$current) {
        $db->rollback();
        respond_json(['error' => 'Order not found'], 404);
    }

    $wasFinal = in_array($current['status'], ['shipping', 'done'], true);
    $willFinal = in_array($status, ['shipping', 'done'], true);
    $hasStockMovements = table_exists($db, 'stock_movements');

    if ($willFinal && !$wasFinal) {
        $itemsStmt = $db->prepare('SELECT oi.product_id, oi.quantity, p.stock FROM order_items oi JOIN products p ON oi.product_id = p.product_id WHERE oi.order_id = ? FOR UPDATE');
        $itemsStmt->bind_param('i', $order_id);
        $itemsStmt->execute();
        $itemsRes = $itemsStmt->get_result();
        $items = $itemsRes->fetch_all(MYSQLI_ASSOC);
        foreach ($items as $item) {
            if ($item['stock'] < $item['quantity']) {
                $db->rollback();
                respond_json(['error' => 'Insufficient stock for order items'], 422);
            }
        }
        foreach ($items as $item) {
            $updateStock = $db->prepare('UPDATE products SET stock = stock - ? WHERE product_id = ?');
            if (!$updateStock) {
                $db->rollback();
                respond_json(['error' => 'Prepare stock update failed'], 500);
            }
            $qty = intval($item['quantity']);
            $pid = intval($item['product_id']);
            $updateStock->bind_param('ii', $qty, $pid);
            if (!$updateStock->execute()) {
                $db->rollback();
                respond_json(['error' => 'Stock update failed'], 500);
            }

            if ($hasStockMovements) {
                $mv = $db->prepare('INSERT INTO stock_movements (product_id, movement_type, delta, ref_type, ref_id, note) VALUES (?, \'ship\', ?, \'order\', ?, ?)');
                if (!$mv) {
                    $db->rollback();
                    respond_json(['error' => 'Prepare movement log failed'], 500);
                }
                $delta = -$qty;
                $note = 'Order #' . $order_id;
                $mv->bind_param('iiis', $pid, $delta, $order_id, $note);
                if (!$mv->execute()) {
                    $db->rollback();
                    respond_json(['error' => 'Movement log failed'], 500);
                }
            }
        }
    }

    $stmt = $db->prepare('UPDATE orders SET status = ? WHERE order_id = ?');
    $stmt->bind_param('si', $status, $order_id);
    if ($stmt->execute()) {
        $db->commit();
        respond_json(['success' => true]);
    }
    $db->rollback();
    respond_json(['error' => 'Update failed'], 500);
}

function list_products(): void {
    $db = get_db();
    $res = $db->query('SELECT product_id, name, category, description, price, stock, image_url, is_active FROM products ORDER BY product_id DESC');
    $products = $res->fetch_all(MYSQLI_ASSOC);
    respond_json(['products' => $products]);
}

function update_product_status(): void {
    $db = get_db();
    $product_id = intval($_POST['product_id'] ?? 0);
    $is_active = intval($_POST['is_active'] ?? 0) ? 1 : 0;
    if (!$product_id) {
        respond_json(['error' => 'Product id required'], 422);
    }
    $stmt = $db->prepare('UPDATE products SET is_active = ? WHERE product_id = ?');
    $stmt->bind_param('ii', $is_active, $product_id);
    if ($stmt->execute()) {
        respond_json(['success' => true]);
    }
    respond_json(['error' => 'Update failed'], 500);
}

function update_product(): void {
    $db = get_db();
    $product_id = intval($_POST['product_id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = floatval($_POST['price'] ?? 0);
    $stock = intval($_POST['stock'] ?? 0);
    $image_url = trim($_POST['image_url'] ?? '');
    if (!$product_id || $name === '') {
        respond_json(['error' => 'Invalid data'], 422);
    }
    $stmt = $db->prepare('UPDATE products SET name = ?, category = ?, description = ?, price = ?, stock = ?, image_url = ? WHERE product_id = ?');
    $stmt->bind_param('sssdisi', $name, $category, $description, $price, $stock, $image_url, $product_id);
    if ($stmt->execute()) {
        respond_json(['success' => true]);
    }
    respond_json(['error' => 'Update failed'], 500);
}

function create_product(): void {
    $db = get_db();
    $name = trim($_POST['name'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = floatval($_POST['price'] ?? 0);
    $stock = intval($_POST['stock'] ?? 0);
    $image_url = trim($_POST['image_url'] ?? '');
    
    if ($name === '' || $price <= 0) {
        respond_json(['error' => 'Invalid data'], 422);
    }
    
    $stmt = $db->prepare('INSERT INTO products (name, category, description, price, stock, image_url, is_active) VALUES (?, ?, ?, ?, ?, ?, 1)');
    $stmt->bind_param('sssdis', $name, $category, $description, $price, $stock, $image_url);
    if ($stmt->execute()) {
        respond_json(['success' => true, 'product_id' => $stmt->insert_id]);
    }
    respond_json(['error' => 'Create failed'], 500);
}

function delete_product(): void {
    $db = get_db();
    $product_id = intval($_POST['product_id'] ?? 0);
    
    if (!$product_id) {
        respond_json(['error' => 'Product id required'], 422);
    }
    
    // 先檢查是否有訂單項目引用此商品
    $checkStmt = $db->prepare('SELECT COUNT(*) AS cnt FROM order_items WHERE product_id = ?');
    $checkStmt->bind_param('i', $product_id);
    $checkStmt->execute();
    $checkRes = $checkStmt->get_result();
    $checkRow = $checkRes->fetch_assoc();
    
    if ($checkRow['cnt'] > 0) {
        respond_json(['error' => '此商品已被訂單引用，無法刪除'], 422);
    }
    
    $stmt = $db->prepare('DELETE FROM products WHERE product_id = ?');
    $stmt->bind_param('i', $product_id);
    if ($stmt->execute()) {
        respond_json(['success' => true]);
    }
    respond_json(['error' => 'Delete failed'], 500);
}

function delete_order(): void {
    $db = get_db();
    $order_id = intval($_POST['order_id'] ?? 0);
    
    if (!$order_id) {
        respond_json(['error' => 'Order id required'], 422);
    }
    
    $db->begin_transaction();
    
    try {
        // 先刪除訂單項目
        $deleteItemsStmt = $db->prepare('DELETE FROM order_items WHERE order_id = ?');
        $deleteItemsStmt->bind_param('i', $order_id);
        $deleteItemsStmt->execute();
        
        // 再刪除訂單
        $deleteOrderStmt = $db->prepare('DELETE FROM orders WHERE order_id = ?');
        $deleteOrderStmt->bind_param('i', $order_id);
        if ($deleteOrderStmt->execute()) {
            $db->commit();
            respond_json(['success' => true]);
        } else {
            $db->rollback();
            respond_json(['error' => 'Delete failed'], 500);
        }
    } catch (Exception $e) {
        $db->rollback();
        respond_json(['error' => 'Delete failed: ' . $e->getMessage()], 500);
    }
}

function dashboard_stats(): void {
    $db = get_db();
    $stats = [
        'pending_count' => 0,
        'today_orders_count' => 0,
        'today_revenue' => 0.0,
        'low_stock_count' => 0,
        'total_orders' => 0,
        'total_revenue' => 0.0
    ];

    $res1 = $db->query("SELECT COUNT(*) AS c FROM orders WHERE status IN ('accepted','preparing')");
    $stats['pending_count'] = intval($res1->fetch_assoc()['c'] ?? 0);

    $res2 = $db->query("SELECT COUNT(*) AS c, COALESCE(SUM(total_amount),0) AS revenue FROM orders WHERE DATE(created_at) = CURDATE()");
    $row2 = $res2->fetch_assoc();
    $stats['today_orders_count'] = intval($row2['c'] ?? 0);
    $stats['today_revenue'] = floatval($row2['revenue'] ?? 0);

    $res3 = $db->query('SELECT COUNT(*) AS c FROM products WHERE stock < 10');
    $stats['low_stock_count'] = intval($res3->fetch_assoc()['c'] ?? 0);

    $res4 = $db->query('SELECT COUNT(*) AS c, COALESCE(SUM(total_amount),0) AS revenue FROM orders');
    $row4 = $res4->fetch_assoc();
    $stats['total_orders'] = intval($row4['c'] ?? 0);
    $stats['total_revenue'] = floatval($row4['revenue'] ?? 0);

    $recentOrders = [];
    $recentRes = $db->query("SELECT o.order_id, COALESCE(m.name, m.email, '訪客') AS customer_name, o.total_amount, o.status, o.created_at
                             FROM orders o
                             LEFT JOIN members m ON m.member_id = o.member_id
                             ORDER BY o.created_at DESC
                             LIMIT 8");
    if ($recentRes) {
        $recentOrders = $recentRes->fetch_all(MYSQLI_ASSOC);
    }

    $topProducts = [];
    $topRes = $db->query("SELECT p.name, p.stock, p.price, COALESCE(SUM(oi.quantity), 0) AS sold_qty
                          FROM products p
                          LEFT JOIN order_items oi ON oi.product_id = p.product_id
                          GROUP BY p.product_id, p.name, p.stock, p.price
                          ORDER BY sold_qty DESC, p.product_id DESC
                          LIMIT 8");
    if ($topRes) {
        $topProducts = $topRes->fetch_all(MYSQLI_ASSOC);
    }

    $dailySales = [];
    $dailyRes = $db->query("SELECT DATE(created_at) AS sale_date, COALESCE(SUM(total_amount),0) AS revenue
                            FROM orders
                            WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 13 DAY)
                            GROUP BY DATE(created_at)
                            ORDER BY sale_date ASC");

    $revenueMap = [];
    if ($dailyRes) {
        while ($row = $dailyRes->fetch_assoc()) {
            $revenueMap[$row['sale_date']] = floatval($row['revenue'] ?? 0);
        }
    }

    for ($i = 13; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i day"));
        $dailySales[] = [
            'date' => $date,
            'revenue' => floatval($revenueMap[$date] ?? 0)
        ];
    }

    respond_json([
        'stats' => $stats,
        'recent_orders' => $recentOrders,
        'top_products' => $topProducts,
        'daily_sales' => $dailySales
    ]);
}

function receiving_products(): void {
    $db = get_db();
    $res = $db->query('SELECT product_id, name, category, price, stock FROM products ORDER BY product_id DESC');
    respond_json(['products' => $res->fetch_all(MYSQLI_ASSOC)]);
}

function receiving_create(): void {
    global $user;
    $db = get_db();
    $product_id = intval($_POST['product_id'] ?? 0);
    $qty = intval($_POST['qty'] ?? 0);
    $supplier_name = trim($_POST['supplier_name'] ?? '');
    $unit_cost_raw = $_POST['unit_cost'] ?? '';
    $unit_cost = $unit_cost_raw === '' ? null : floatval($unit_cost_raw);
    $note = trim($_POST['note'] ?? '');
    $received_at = trim($_POST['received_at'] ?? '');

    if (!$product_id || $qty <= 0 || ($unit_cost !== null && $unit_cost < 0)) {
        respond_json(['error' => 'Invalid data'], 422);
    }

    if ($received_at !== '') {
        $dt = DateTime::createFromFormat('Y-m-d H:i:s', $received_at);
        if (!$dt) {
            respond_json(['error' => 'Invalid received_at'], 422);
        }
        $received_at = $dt->format('Y-m-d H:i:s');
    } else {
        $received_at = date('Y-m-d H:i:s');
    }

    $db->begin_transaction();
    $productStmt = $db->prepare('SELECT product_id FROM products WHERE product_id = ? FOR UPDATE');
    $productStmt->bind_param('i', $product_id);
    $productStmt->execute();
    $productRow = $productStmt->get_result()->fetch_assoc();
    if (!$productRow) {
        $db->rollback();
        respond_json(['error' => 'Product not found'], 404);
    }

    $subtotal = $unit_cost !== null ? $unit_cost * $qty : null;
    $total_lines = 1;
    $total_cost = $subtotal;
    $noteVal = $note === '' ? null : $note;
    $supplierVal = $supplier_name === '' ? null : $supplier_name;
    $adminId = $user['member_id'] ?? null;

    $headerStmt = $db->prepare('INSERT INTO receiving_headers (supplier_name, total_lines, total_cost, note, received_at, created_by_admin_id) VALUES (?, ?, ?, ?, ?, ?)');
    $headerStmt->bind_param('sidssi', $supplierVal, $total_lines, $total_cost, $noteVal, $received_at, $adminId);
    if (!$headerStmt->execute()) {
        $db->rollback();
        respond_json(['error' => 'Create receiving header failed'], 500);
    }
    $receiving_id = $headerStmt->insert_id;

    $itemStmt = $db->prepare('INSERT INTO receiving_items (receiving_id, product_id, qty, unit_cost, subtotal_cost) VALUES (?, ?, ?, ?, ?)');
    $itemStmt->bind_param('iiidd', $receiving_id, $product_id, $qty, $unit_cost, $subtotal);
    if (!$itemStmt->execute()) {
        $db->rollback();
        respond_json(['error' => 'Create receiving item failed'], 500);
    }

    $stockStmt = $db->prepare('UPDATE products SET stock = stock + ? WHERE product_id = ?');
    $stockStmt->bind_param('ii', $qty, $product_id);
    if (!$stockStmt->execute()) {
        $db->rollback();
        respond_json(['error' => 'Stock update failed'], 500);
    }

    $mvStmt = $db->prepare('INSERT INTO stock_movements (product_id, movement_type, delta, ref_type, ref_id, note) VALUES (?, \'receive\', ?, \'receiving\', ?, ?)');
    $delta = $qty;
    $mvStmt->bind_param('iiis', $product_id, $delta, $receiving_id, $noteVal);
    if (!$mvStmt->execute()) {
        $db->rollback();
        respond_json(['error' => 'Movement log failed'], 500);
    }

    $db->commit();
    respond_json(['success' => true, 'receiving_id' => $receiving_id]);
}

function receiving_list(): void {
    $db = get_db();
    $res = $db->query('SELECT receiving_id, supplier_name, total_lines, total_cost, note, received_at, created_by_admin_id, created_at FROM receiving_headers ORDER BY received_at DESC LIMIT 200');
    respond_json(['receivings' => $res->fetch_all(MYSQLI_ASSOC)]);
}

function receiving_detail(): void {
    $db = get_db();
    $receiving_id = intval($_GET['receiving_id'] ?? 0);
    if (!$receiving_id) {
        respond_json(['error' => 'Invalid receiving id'], 422);
    }
    $headerStmt = $db->prepare('SELECT receiving_id, supplier_name, total_lines, total_cost, note, received_at, created_by_admin_id, created_at FROM receiving_headers WHERE receiving_id = ?');
    $headerStmt->bind_param('i', $receiving_id);
    $headerStmt->execute();
    $header = $headerStmt->get_result()->fetch_assoc();
    if (!$header) {
        respond_json(['error' => 'Receiving not found'], 404);
    }

    $itemsStmt = $db->prepare('SELECT ri.product_id, p.name, ri.qty, ri.unit_cost, ri.subtotal_cost FROM receiving_items ri JOIN products p ON ri.product_id = p.product_id WHERE ri.receiving_id = ?');
    $itemsStmt->bind_param('i', $receiving_id);
    $itemsStmt->execute();
    $items = $itemsStmt->get_result()->fetch_all(MYSQLI_ASSOC);

    respond_json(['header' => $header, 'items' => $items]);
}

function stock_movements_list(): void {
    $db = get_db();
    $product_id = intval($_GET['product_id'] ?? 0);
    if ($product_id > 0) {
        $stmt = $db->prepare('SELECT sm.movement_id, sm.product_id, p.name, sm.movement_type, sm.delta, sm.ref_type, sm.ref_id, sm.note, sm.created_at FROM stock_movements sm JOIN products p ON sm.product_id = p.product_id WHERE sm.product_id = ? ORDER BY sm.created_at DESC, sm.movement_id DESC LIMIT 200');
        $stmt->bind_param('i', $product_id);
        $stmt->execute();
        $res = $stmt->get_result();
    } else {
        $res = $db->query('SELECT sm.movement_id, sm.product_id, p.name, sm.movement_type, sm.delta, sm.ref_type, sm.ref_id, sm.note, sm.created_at FROM stock_movements sm JOIN products p ON sm.product_id = p.product_id ORDER BY sm.created_at DESC, sm.movement_id DESC LIMIT 200');
    }
    respond_json(['movements' => $res->fetch_all(MYSQLI_ASSOC)]);
}

function stock_adjust(): void {
    $db = get_db();
    $product_id = intval($_POST['product_id'] ?? 0);
    $delta = intval($_POST['delta'] ?? 0);
    $reason = trim($_POST['reason'] ?? '');
    if (!$product_id || $delta === 0 || $reason === '') {
        respond_json(['error' => 'Invalid data'], 422);
    }

    $db->begin_transaction();
    $stmt = $db->prepare('SELECT stock FROM products WHERE product_id = ? FOR UPDATE');
    $stmt->bind_param('i', $product_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    if (!$row) {
        $db->rollback();
        respond_json(['error' => 'Product not found'], 404);
    }
    $current = intval($row['stock']);
    $newStock = $current + $delta;
    if ($newStock < 0) {
        $db->rollback();
        respond_json(['error' => 'Stock cannot be negative'], 422);
    }

    $update = $db->prepare('UPDATE products SET stock = ? WHERE product_id = ?');
    $update->bind_param('ii', $newStock, $product_id);
    if (!$update->execute()) {
        $db->rollback();
        respond_json(['error' => 'Stock update failed'], 500);
    }

    $mv = $db->prepare('INSERT INTO stock_movements (product_id, movement_type, delta, ref_type, ref_id, note) VALUES (?, \'adjust\', ?, \'manual\', NULL, ?)');
    $mv->bind_param('iis', $product_id, $delta, $reason);
    if (!$mv->execute()) {
        $db->rollback();
        respond_json(['error' => 'Movement log failed'], 500);
    }

    $db->commit();
    respond_json(['success' => true]);
}

function get_all_chats(): void {
    $db = get_db();
    
    // Group by user_id or session_id to get unique conversations
    // We want the last message time and content for the list
    $sql = "
        SELECT 
            COALESCE(user_id, session_id) as chat_id,
            MAX(created_at) as last_activity,
            (SELECT message FROM chat_messages m2 WHERE (m2.user_id = m1.user_id OR m2.session_id = m1.session_id) ORDER BY created_at DESC LIMIT 1) as last_message
        FROM chat_messages m1
        GROUP BY COALESCE(user_id, session_id)
        ORDER BY last_activity DESC
    ";
    
    $res = $db->query($sql);
    if ($res) {
        $chats = $res->fetch_all(MYSQLI_ASSOC);
        respond_json(['success' => true, 'chats' => $chats]);
    } else {
        respond_json(['error' => 'Db error'], 500);
    }
}

function get_chat_history(): void {
    $db = get_db();
    $chatId = $_GET['chat_id'] ?? '';
    
    if (!$chatId) {
        respond_json(['error' => 'Missing chat_id'], 400);
    }
    
    $isUser = is_numeric($chatId);
    
    if ($isUser) {
        $stmt = $db->prepare("SELECT * FROM chat_messages WHERE user_id = ? ORDER BY created_at ASC");
        $stmt->bind_param("i", $chatId);
    } else {
        $stmt = $db->prepare("SELECT * FROM chat_messages WHERE session_id = ? ORDER BY created_at ASC");
        $stmt->bind_param("s", $chatId);
    }
    
    $stmt->execute();
    $messages = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    respond_json(['success' => true, 'messages' => $messages]);
}

function reply_chat(): void {
    $db = get_db();

    // 後台使用 api.post(...)，傳的是 x-www-form-urlencoded，所以用 $_POST 讀取
    $message = trim($_POST['message'] ?? '');
    $userId = $_POST['user_id'] ?? null;
    $sessionId = $_POST['session_id'] ?? null;

    if ($userId !== null && $userId !== '') {
        $userId = (int)$userId;
    } else {
        $userId = null;
    }

    if ($sessionId !== null && $sessionId !== '') {
        $sessionId = (string)$sessionId;
    } else {
        $sessionId = null;
    }

    if ($message === '' || ($userId === null && $sessionId === null)) {
        respond_json(['error' => 'Invalid data'], 400);
    }

    if ($userId !== null) {
        $stmt = $db->prepare("INSERT INTO chat_messages (user_id, sender, message) VALUES (?, 'admin', ?)");
        $stmt->bind_param("is", $userId, $message);
    } else {
        $stmt = $db->prepare("INSERT INTO chat_messages (session_id, sender, message) VALUES (?, 'admin', ?)");
        $stmt->bind_param("ss", $sessionId, $message);
    }

    if ($stmt->execute()) {
        respond_json(['success' => true]);
    }

    respond_json(['error' => 'Db error'], 500);
}
