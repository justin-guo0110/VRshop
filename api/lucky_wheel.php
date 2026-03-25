<?php
require_once __DIR__ . '/db.php';

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

function get_order_total_column(mysqli $db): string {
    if (column_exists($db, 'orders', 'total_price')) {
        return 'total_price';
    }
    return 'total_amount';
}

$action = $_GET['action'] ?? '';
$user = require_login();

switch ($action) {
    case 'get_spin_info':
        get_spin_info($user);
        break;
    case 'spin':
        perform_spin($user);
        break;
    case 'list_coupons':
        list_coupons($user);
        break;
    case 'use_coupon':
        use_coupon($user);
        break;
    default:
        respond_json(['error' => 'Unknown action'], 400);
}

/**
 * 幸运转盘8个奖项配置
 */
function get_wheel_prizes(): array {
    $defaults = [
        ['id' => 0, 'name' => '折扣10%', 'discount_type' => 'percent', 'value' => 10, 'min_purchase' => 0],
        ['id' => 1, 'name' => '折扣15%', 'discount_type' => 'percent', 'value' => 15, 'min_purchase' => 100],
        ['id' => 2, 'name' => '折扣20%', 'discount_type' => 'percent', 'value' => 20, 'min_purchase' => 200],
        ['id' => 3, 'name' => '折扣$30', 'discount_type' => 'fixed', 'value' => 30, 'min_purchase' => 200],
        ['id' => 4, 'name' => '免運費', 'discount_type' => 'fixed', 'value' => 50, 'min_purchase' => 500],
        ['id' => 5, 'name' => '折扣$50', 'discount_type' => 'fixed', 'value' => 50, 'min_purchase' => 300],
        ['id' => 6, 'name' => '折扣$20', 'discount_type' => 'fixed', 'value' => 20, 'min_purchase' => 100],
        ['id' => 7, 'name' => '立減$100', 'discount_type' => 'fixed', 'value' => 100, 'min_purchase' => 500],
    ];

    $path = __DIR__ . '/../storage/lucky_wheel_prizes.json';
    if (!is_file($path)) {
        return $defaults;
    }

    $content = @file_get_contents($path);
    if ($content === false || trim($content) === '') {
        return $defaults;
    }

    $decoded = json_decode($content, true);
    if (!is_array($decoded) || count($decoded) !== 8) {
        return $defaults;
    }

    $normalized = [];
    foreach ($decoded as $index => $row) {
        if (!is_array($row)) return $defaults;
        $type = ($row['discount_type'] ?? 'fixed') === 'percent' ? 'percent' : 'fixed';
        $name = trim((string)($row['name'] ?? ''));
        if ($name === '') return $defaults;
        $normalized[] = [
            'id' => intval($row['id'] ?? $index),
            'name' => $name,
            'discount_type' => $type,
            'value' => max(0, floatval($row['value'] ?? 0)),
            'min_purchase' => max(0, floatval($row['min_purchase'] ?? 0)),
        ];
    }

    if (count($normalized) !== 8) {
        return $defaults;
    }

    return $normalized;
}

/**
 * 获取用户的转盘次数信息
 */
function get_spin_info(array $user): void {
    $db = get_db();
    $member_id = $user['member_id'];
    $totalColumn = get_order_total_column($db);

    // 计算用户的总订单金额
    $stmt = $db->prepare("SELECT SUM($totalColumn) as total FROM orders WHERE member_id = ?");
    if (!$stmt) {
        respond_json(['error' => '初始化轉盤失敗'], 500);
    }
    $stmt->bind_param('i', $member_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $total_spent = floatval($result['total'] ?? 0);

    // 购物满500可以转一次，按此类推
    $spin_count = intval($total_spent / 500);

    // 查询今天已转的次数
    $today = date('Y-m-d');
    $stmt = $db->prepare('SELECT COUNT(*) as spins_today FROM lucky_wheel_spins WHERE member_id = ? AND spin_date = ?');
    $stmt->bind_param('is', $member_id, $today);
    $stmt->execute();
    $today_result = $stmt->get_result()->fetch_assoc();
    $spins_today = intval($today_result['spins_today'] ?? 0);

    $can_spin = $spin_count > $spins_today;

    respond_json([
        'total_spent' => $total_spent,
        'spin_count' => $spin_count,
        'spins_today' => $spins_today,
        'can_spin' => $can_spin,
        'remaining_spins' => max(0, $spin_count - $spins_today),
        'prizes' => get_wheel_prizes()
    ]);
}

/**
 * 执行转盘抽奖
 */
function perform_spin(array $user): void {
    $db = get_db();
    $member_id = $user['member_id'];

    // 验证用户是否有转盘次数
    $info = get_spin_info_data($db, $member_id);
    if (!$info['can_spin']) {
        respond_json(['error' => '今日转盘次数已用完'], 422);
    }

    // 随机选择奖项（加权随机，让好奖项概率更低）
    $prizes = get_wheel_prizes();
    $weights = [15, 20, 15, 20, 8, 15, 5, 2]; // 对应8个奖项的权重
    
    $selected_index = weighted_random($weights);
    $prize = $prizes[$selected_index];

    // 生成优惠券
    $coupon_id = null;
    $coupon_id = create_coupon($db, $member_id, $prize);
    if (!$coupon_id) {
        respond_json(['error' => '生成优惠券失败'], 500);
    }

    // 记录转盘记录
    $today = date('Y-m-d');
    $stmt = $db->prepare('INSERT INTO lucky_wheel_spins (member_id, spin_date, result_index, coupon_id) VALUES (?, ?, ?, ?)');
    $stmt->bind_param('isii', $member_id, $today, $selected_index, $coupon_id);
    
    if ($stmt->execute()) {
        respond_json([
            'success' => true,
            'prize' => $prize,
            'prize_index' => $selected_index,
            'coupon' => get_coupon_detail($db, $coupon_id),
            'message' => '恭喜！获得優惠券！'
        ]);
    } else {
        respond_json(['error' => '转盘失败，请稍后重试'], 500);
    }
}

/**
 * 获取转盘信息数据（不包含响应）
 */
function get_spin_info_data($db, $member_id): array {
    $totalColumn = get_order_total_column($db);

    // 计算用户的总订单金额
    $stmt = $db->prepare("SELECT SUM($totalColumn) as total FROM orders WHERE member_id = ?");
    if (!$stmt) {
        return [
            'total_spent' => 0,
            'spin_count' => 0,
            'spins_today' => 0,
            'can_spin' => false,
            'remaining_spins' => 0
        ];
    }
    $stmt->bind_param('i', $member_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $total_spent = floatval($result['total'] ?? 0);

    // 购物满500可以转一次，按此类推
    $spin_count = intval($total_spent / 500);

    // 查询今天已转的次数
    $today = date('Y-m-d');
    $stmt = $db->prepare('SELECT COUNT(*) as spins_today FROM lucky_wheel_spins WHERE member_id = ? AND spin_date = ?');
    $stmt->bind_param('is', $member_id, $today);
    $stmt->execute();
    $today_result = $stmt->get_result()->fetch_assoc();
    $spins_today = intval($today_result['spins_today'] ?? 0);

    $can_spin = $spin_count > $spins_today;

    return [
        'total_spent' => $total_spent,
        'spin_count' => $spin_count,
        'spins_today' => $spins_today,
        'can_spin' => $can_spin,
        'remaining_spins' => max(0, $spin_count - $spins_today)
    ];
}

/**
 * 加权随机化
 */
function weighted_random(array $weights): int {
    $sum = array_sum($weights);
    $rand = mt_rand(1, $sum);
    $current = 0;
    foreach ($weights as $i => $weight) {
        $current += $weight;
        if ($rand <= $current) {
            return $i;
        }
    }
    return count($weights) - 1;
}

/**
 * 创建优惠券
 */
function create_coupon($db, $member_id, $prize): ?int {
    $coupon_code = 'VR' . strtoupper(uniqid());
    $discount_type = $prize['discount_type'];
    $value = $prize['value'];
    $min_purchase = $prize['min_purchase'];
    $expiry = date('Y-m-d H:i:s', strtotime('+30 days'));
    $description = $prize['name'];

    $stmt = $db->prepare(
        'INSERT INTO coupons (member_id, coupon_code, discount_type, discount_value, min_purchase, expiry_date, description) 
         VALUES (?, ?, ?, ?, ?, ?, ?)'
    );
    $stmt->bind_param('issddss', $member_id, $coupon_code, $discount_type, $value, $min_purchase, $expiry, $description);

    if ($stmt->execute()) {
        return $stmt->insert_id;
    }
    return null;
}

/**
 * 获取优惠券详情
 */
function get_coupon_detail($db, $coupon_id): array {
    $stmt = $db->prepare('SELECT coupon_id, coupon_code, discount_type, discount_value, min_purchase, expiry_date, description FROM coupons WHERE coupon_id = ?');
    $stmt->bind_param('i', $coupon_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    return $result ?: [];
}

/**
 * 获取用户的优惠券列表
 */
function list_coupons(array $user): void {
    $db = get_db();
    $member_id = $user['member_id'];
    $only_active = isset($_GET['only_active']) ? intval($_GET['only_active']) : 1;

    $sql = 'SELECT coupon_id, coupon_code, discount_type, discount_value, min_purchase, used_count, max_usage, expiry_date, description, is_active, created_at 
            FROM coupons WHERE member_id = ?';
    
    if ($only_active) {
        $sql .= ' AND is_active = 1 AND expiry_date > NOW()';
    }
    
    $sql .= ' ORDER BY created_at DESC';

    $stmt = $db->prepare($sql);
    $stmt->bind_param('i', $member_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $coupons = $result->fetch_all(MYSQLI_ASSOC);

    respond_json(['coupons' => $coupons]);
}

/**
 * 使用优惠券
 */
function use_coupon(array $user): void {
    $db = get_db();
    $member_id = $user['member_id'];
    $coupon_code = trim($_POST['coupon_code'] ?? '');

    if ($coupon_code === '') {
        respond_json(['error' => 'Coupon code required'], 422);
    }

    // 验证优惠券
    $stmt = $db->prepare('SELECT coupon_id, used_count, max_usage, discount_type, discount_value, min_purchase, expiry_date 
                          FROM coupons WHERE coupon_code = ? AND member_id = ?');
    $stmt->bind_param('si', $coupon_code, $member_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();

    if (!$result) {
        respond_json(['error' => '优惠券不存在或无效'], 404);
    }

    if ($result['used_count'] >= $result['max_usage']) {
        respond_json(['error' => '优惠券已使用过'], 422);
    }

    if (strtotime($result['expiry_date']) < time()) {
        respond_json(['error' => '优惠券已过期'], 422);
    }

    // 更新使用次数
    $new_count = $result['used_count'] + 1;
    $stmt = $db->prepare('UPDATE coupons SET used_count = ? WHERE coupon_code = ?');
    $stmt->bind_param('is', $new_count, $coupon_code);

    if ($stmt->execute()) {
        respond_json([
            'success' => true,
            'coupon' => [
                'code' => $coupon_code,
                'discount_type' => $result['discount_type'],
                'discount_value' => $result['discount_value'],
                'min_purchase' => $result['min_purchase']
            ]
        ]);
    } else {
        respond_json(['error' => '使用优惠券失败'], 500);
    }
}
