<?php
require_once __DIR__ . '/db.php';

function promo_load_db_config(mysqli $db): array {
    $dbRules = [
        'shipping_fees' => [],
        'free_shipping_thresholds' => [],
        'bundle_rules' => [],
    ];

    $result = $db->query('SELECT config_type, config_key, config_value FROM promotion_config WHERE is_active = 1');
    if (!$result) {
        return [];
    }

    $bundleConfig = [];
    while ($row = $result->fetch_assoc()) {
        $type = (string)$row['config_type'];
        $key = (string)$row['config_key'];
        $val = (string)$row['config_value'];

        if ($type === 'shipping') {
            if ($key === 'home_fee') {
                $dbRules['shipping_fees']['宅配'] = floatval($val);
            } elseif ($key === 'convenience_fee') {
                $dbRules['shipping_fees']['超商取貨'] = floatval($val);
            } elseif ($key === 'home_threshold') {
                $dbRules['free_shipping_thresholds']['宅配'] = floatval($val);
            } elseif ($key === 'convenience_threshold') {
                $dbRules['free_shipping_thresholds']['超商取貨'] = floatval($val);
            }
        } elseif ($type === 'bundle') {
            if ($key === 'beverage_discount_qty') {
                $bundleConfig['beverage_qty'] = intval($val);
            } elseif ($key === 'beverage_discount_percent') {
                $bundleConfig['beverage_percent'] = floatval($val);
            } elseif ($key === 'beverage_categories') {
                $categories = promo_parse_json_value($val);
                $bundleConfig['beverage_categories'] = is_array($categories) ? $categories : [];
            } elseif ($key === 'snack_discount_qty') {
                $bundleConfig['snack_qty'] = intval($val);
            } elseif ($key === 'snack_discount_fixed') {
                $bundleConfig['snack_fixed'] = floatval($val);
            } elseif ($key === 'snack_categories') {
                $categories = promo_parse_json_value($val);
                $bundleConfig['snack_categories'] = is_array($categories) ? $categories : [];
            }
        }
    }

    if (!empty($bundleConfig['beverage_qty']) && !empty($bundleConfig['beverage_percent'])) {
        $dbRules['bundle_rules'][] = [
            'code' => 'beverage_2_88',
            'label' => '飲料任選 ' . intval($bundleConfig['beverage_qty']) . ' 件 ' . (100 - intval($bundleConfig['beverage_percent'])) . '折',
            'type' => 'percent',
            'value' => $bundleConfig['beverage_percent'],
            'categories' => $bundleConfig['beverage_categories'] ?? ['咖啡', '奶類', '巧克力', '果汁', '碳酸飲料', '茶類', '運動飲料'],
        ];
    }

    if (!empty($bundleConfig['snack_qty']) && !empty($bundleConfig['snack_fixed'])) {
        $dbRules['bundle_rules'][] = [
            'code' => 'snack_3_minus_20',
            'label' => '零食任選 ' . intval($bundleConfig['snack_qty']) . ' 件折 $' . intval($bundleConfig['snack_fixed']),
            'type' => 'fixed_per_step',
            'value' => $bundleConfig['snack_fixed'],
            'step' => $bundleConfig['snack_qty'],
            'categories' => $bundleConfig['snack_categories'] ?? ['糖果', '膨化零食', '餅乾'],
        ];
    }

    return $dbRules;
}

function promo_parse_json_value(string $val): mixed {
    if (strpos($val, 'json:') === 0) {
        $json = substr($val, 5);
        $decoded = json_decode($json, true);
        return is_array($decoded) ? $decoded : null;
    }
    return null;
}

function promo_get_rules(): array {
    static $rules = null;
    if ($rules !== null) {
        return $rules;
    }

    $defaultRules = [
        'welcome_coupon' => [
            'prefix' => 'WELCOME',
            'discount_type' => 'percent',
            'discount_value' => 10,
            'min_purchase' => 199,
            'max_usage' => 1,
            'expiry_days' => 30,
            'description' => '新會員首購 9 折券'
        ],
        'shipping_fees' => [
            '宅配' => 100,
            '超商取貨' => 60,
        ],
        'free_shipping_thresholds' => [
            '宅配' => 499,
            '超商取貨' => 299,
        ],
        'bundle_rules' => [
            [
                'code' => 'beverage_2_88',
                'label' => '飲料任選 2 件 88 折',
                'type' => 'percent',
                'value' => 12,
                'categories' => ['咖啡', '奶類', '巧克力', '果汁', '碳酸飲料', '茶類', '運動飲料'],
            ],
            [
                'code' => 'snack_3_minus_20',
                'label' => '零食任選 3 件折 $20',
                'type' => 'fixed_per_step',
                'value' => 20,
                'step' => 3,
                'categories' => ['糖果', '膨化零食', '餅乾'],
            ],
        ],
    ];

    try {
        $db = get_db();
        $dbRules = promo_load_db_config($db);
        
        if (!empty($dbRules['shipping_fees'])) {
            $defaultRules['shipping_fees'] = array_merge($defaultRules['shipping_fees'], $dbRules['shipping_fees']);
        }
        if (!empty($dbRules['free_shipping_thresholds'])) {
            $defaultRules['free_shipping_thresholds'] = array_merge($defaultRules['free_shipping_thresholds'], $dbRules['free_shipping_thresholds']);
        }
        if (!empty($dbRules['bundle_rules'])) {
            $defaultRules['bundle_rules'] = $dbRules['bundle_rules'];
        }
    } catch (Throwable $e) {
    }

    $rules = $defaultRules;
    return $rules;
}

function promo_get_shipping_quote(string $shippingMethod, float $subtotal): array {
    $rules = promo_get_rules();
    $baseFee = floatval($rules['shipping_fees'][$shippingMethod] ?? 0);
    $threshold = isset($rules['free_shipping_thresholds'][$shippingMethod])
        ? floatval($rules['free_shipping_thresholds'][$shippingMethod])
        : null;
    $freeShipping = $threshold !== null && $subtotal >= $threshold;

    return [
        'base_fee' => $baseFee,
        'threshold' => $threshold,
        'free_shipping' => $freeShipping,
        'shipping_fee' => $freeShipping ? 0.0 : $baseFee,
    ];
}

function promo_calculate_bundle_discount(array $items): array {
    $rules = promo_get_rules();
    $details = [];
    $totalDiscount = 0.0;

    foreach ($rules['bundle_rules'] as $rule) {
        $matchedQty = 0;
        $matchedSubtotal = 0.0;
        foreach ($items as $item) {
            $category = trim((string)($item['category'] ?? ''));
            if ($category === '' || !in_array($category, $rule['categories'], true)) {
                continue;
            }
            $qty = max(0, intval($item['quantity'] ?? 0));
            $price = max(0, floatval($item['price'] ?? 0));
            $matchedQty += $qty;
            $matchedSubtotal += $price * $qty;
        }

        if ($matchedQty <= 0 || $matchedSubtotal <= 0) {
            continue;
        }

        $discount = 0.0;
        if ($rule['type'] === 'percent' && $matchedQty >= 2) {
            $discount = round($matchedSubtotal * (floatval($rule['value']) / 100), 2);
        }
        if ($rule['type'] === 'fixed_per_step' && $matchedQty >= intval($rule['step'] ?? 0)) {
            $discount = floor($matchedQty / intval($rule['step'])) * floatval($rule['value']);
            if ($discount > $matchedSubtotal) {
                $discount = $matchedSubtotal;
            }
            $discount = round($discount, 2);
        }

        if ($discount <= 0) {
            continue;
        }

        $details[] = [
            'code' => $rule['code'],
            'label' => $rule['label'],
            'discount' => $discount,
            'matched_quantity' => $matchedQty,
        ];
        $totalDiscount += $discount;
    }

    return [
        'discount' => round($totalDiscount, 2),
        'details' => $details,
    ];
}

function promo_ensure_coupons_table(mysqli $db): void {
    $db->query(
        'CREATE TABLE IF NOT EXISTS coupons (
            coupon_id INT(11) NOT NULL AUTO_INCREMENT,
            member_id INT(11) NOT NULL,
            coupon_code VARCHAR(50) NOT NULL,
            discount_type ENUM("percent","fixed") NOT NULL DEFAULT "fixed",
            discount_value DECIMAL(10,2) NOT NULL,
            min_purchase DECIMAL(10,2) DEFAULT 0,
            max_usage INT(11) DEFAULT 1,
            used_count INT(11) DEFAULT 0,
            expiry_date DATETIME DEFAULT NULL,
            description VARCHAR(255) DEFAULT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            is_active TINYINT(1) DEFAULT 1,
            PRIMARY KEY (coupon_id),
            UNIQUE KEY uk_coupon_code (coupon_code),
            KEY idx_member_expiry (member_id, expiry_date),
            CONSTRAINT fk_coupon_member FOREIGN KEY (member_id) REFERENCES members(member_id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );
}

function promo_generate_coupon_code(string $prefix): string {
    return strtoupper($prefix) . strtoupper(bin2hex(random_bytes(4)));
}

function promo_issue_welcome_coupon(mysqli $db, int $memberId): ?array {
    if ($memberId <= 0) {
        return null;
    }

    promo_ensure_coupons_table($db);
    $config = promo_get_rules()['welcome_coupon'];
    $like = $config['prefix'] . '%';

    $existingStmt = $db->prepare('SELECT coupon_id, coupon_code, discount_type, discount_value, min_purchase, expiry_date, description FROM coupons WHERE member_id = ? AND coupon_code LIKE ? ORDER BY coupon_id DESC LIMIT 1');
    $existingStmt->bind_param('is', $memberId, $like);
    $existingStmt->execute();
    $existing = $existingStmt->get_result()->fetch_assoc();
    if ($existing) {
        return $existing;
    }

    $expiry = date('Y-m-d H:i:s', strtotime('+' . intval($config['expiry_days']) . ' days'));
    $insertStmt = $db->prepare('INSERT INTO coupons (member_id, coupon_code, discount_type, discount_value, min_purchase, max_usage, used_count, expiry_date, description, is_active) VALUES (?, ?, ?, ?, ?, ?, 0, ?, ?, 1)');

    for ($attempt = 0; $attempt < 5; $attempt++) {
        $code = promo_generate_coupon_code($config['prefix']);
        $discountType = (string)$config['discount_type'];
        $discountValue = floatval($config['discount_value']);
        $minPurchase = floatval($config['min_purchase']);
        $maxUsage = intval($config['max_usage']);
        $description = (string)$config['description'];
        $insertStmt->bind_param('issddiss', $memberId, $code, $discountType, $discountValue, $minPurchase, $maxUsage, $expiry, $description);
        if ($insertStmt->execute()) {
            return [
                'coupon_id' => $insertStmt->insert_id,
                'coupon_code' => $code,
                'discount_type' => $discountType,
                'discount_value' => $discountValue,
                'min_purchase' => $minPurchase,
                'expiry_date' => $expiry,
                'description' => $description,
            ];
        }
        if ($db->errno !== 1062) {
            break;
        }
    }

    return null;
}

function promo_is_welcome_coupon(array $couponRow): bool {
    $prefix = strtoupper((string)(promo_get_rules()['welcome_coupon']['prefix'] ?? 'WELCOME'));
    $code = strtoupper((string)($couponRow['coupon_code'] ?? ''));
    return $code !== '' && strpos($code, $prefix) === 0;
}