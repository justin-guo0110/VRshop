<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/promotion_helpers.php';

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'get_config':
        get_promotion_config();
        break;
    case 'update_shipping':
        update_shipping_config();
        break;
    case 'update_bundle':
        update_bundle_config();
        break;
    default:
        respond_json(['error' => 'Unknown action'], 400);
}

function get_promotion_config(): void {
    $user = require_login();
    if (!isset($user['role']) || $user['role'] !== 'admin') {
        respond_json(['error' => '只有管理員可以存取'], 403);
    }

    $db = get_db();
    
    // 獲取當前配置
    $result = $db->query('SELECT config_type, config_key, config_value, description FROM promotion_config WHERE is_active = 1 ORDER BY config_type, config_key');
    
    $shippingConfig = [];
    $bundleConfig = [];
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $type = (string)$row['config_type'];
            $key = (string)$row['config_key'];
            $val = (string)$row['config_value'];
            
            if ($type === 'shipping') {
                $shippingConfig[$key] = [
                    'value' => $val,
                    'description' => $row['description']
                ];
            } elseif ($type === 'bundle') {
                if ($key === 'bundle_rules') {
                    $bundleRules = promo_parse_json_value($val);
                    if (is_array($bundleRules)) {
                        $bundleConfig['bundle_rules'] = $bundleRules;
                    }
                } else {
                    $bundleConfig[$key] = [
                        'value' => $val,
                        'description' => $row['description']
                    ];
                }
            }
        }
    }
    
    // 獲取預設規則用於顯示
    $rules = promo_get_rules();
    
    respond_json([
        'success' => true,
        'shipping' => $shippingConfig,
        'bundle' => $bundleConfig,
        'bundle_rules' => $bundleConfig['bundle_rules'] ?? [],
        'current_rules' => $rules
    ]);
}

function update_shipping_config(): void {
    $user = require_login();
    if (!isset($user['role']) || $user['role'] !== 'admin') {
        respond_json(['error' => '只有管理員可以存取'], 403);
    }

    $homeFee = floatval($_POST['home_fee'] ?? 0);
    $convenienceFee = floatval($_POST['convenience_fee'] ?? 0);
    $homeThreshold = floatval($_POST['home_threshold'] ?? 0);
    $convenienceThreshold = floatval($_POST['convenience_threshold'] ?? 0);

    if ($homeFee < 0 || $convenienceFee < 0 || $homeThreshold < 0 || $convenienceThreshold < 0) {
        respond_json(['error' => '所有值必須為正數'], 422);
    }

    $db = get_db();
    $memberId = intval($user['member_id'] ?? 0);

    try {
        $db->begin_transaction();

        // 更新各個配置
        $configs = [
            ['home_fee', floatval($homeFee)],
            ['convenience_fee', floatval($convenienceFee)],
            ['home_threshold', floatval($homeThreshold)],
            ['convenience_threshold', floatval($convenienceThreshold)]
        ];

        $stmt = $db->prepare('UPDATE promotion_config SET config_value = ?, updated_by = ?, updated_at = NOW() WHERE config_type = "shipping" AND config_key = ?');
        foreach ($configs as [$key, $value]) {
            $stmt->bind_param('dis', $value, $memberId, $key);
            if (!$stmt->execute()) {
                throw new Exception('Failed to update config');
            }
        }

        $db->commit();
        respond_json(['success' => true, 'message' => '運費設定已更新']);
    } catch (Throwable $e) {
        $db->rollback();
        respond_json(['error' => 'Update failed: ' . $e->getMessage()], 500);
    }
}

function update_bundle_config(): void {
    $user = require_login();
    if (!isset($user['role']) || $user['role'] !== 'admin') {
        respond_json(['error' => '只有管理員可以存取'], 403);
    }

    $bundleRulesJson = trim((string)($_POST['bundle_rules_json'] ?? ''));
    $db = get_db();
    $memberId = intval($user['member_id'] ?? 0);

    try {
        $db->begin_transaction();

        if ($bundleRulesJson !== '') {
            $bundleRules = json_decode($bundleRulesJson, true);
            if (!is_array($bundleRules)) {
                throw new Exception('Invalid bundle rules format');
            }

            foreach ($bundleRules as $rule) {
                if (!is_array($rule) || empty($rule['label']) || empty($rule['type'])) {
                    throw new Exception('每一條自訂規則都需要名稱和類型');
                }
                $type = (string)$rule['type'];
                if ($type === 'gift') {
                    $rewardProductId = intval($rule['reward']['product_id'] ?? ($rule['reward_product_id'] ?? 0));
                    if ($rewardProductId <= 0) {
                        throw new Exception('贈品規則必須設定贈品商品ID');
                    }
                }
            }

            $payload = json_encode($bundleRules, JSON_UNESCAPED_UNICODE);
            if ($payload === false) {
                throw new Exception('Invalid bundle rules payload');
            }

            $sql = 'INSERT INTO promotion_config (config_type, config_key, config_value, description, is_active)
                    VALUES ("bundle", "bundle_rules", ?, "組合優惠規則", 1)
                    ON DUPLICATE KEY UPDATE config_value = VALUES(config_value), description = VALUES(description), updated_at = NOW()';
            $stmt = $db->prepare($sql);
            if (!$stmt) {
                throw new Exception('Failed to prepare bundle rules statement');
            }
            $stmt->bind_param('s', $payload);
            if (!$stmt->execute()) {
                throw new Exception('Failed to save bundle rules');
            }

            $db->commit();
            respond_json(['success' => true, 'message' => '自訂組合優惠規則已更新']);
            return;
        }

        $beverageQty = intval($_POST['beverage_qty'] ?? 2);
        $beveragePercent = floatval($_POST['beverage_percent'] ?? 12);
        $snackQty = intval($_POST['snack_qty'] ?? 3);
        $snackFixed = floatval($_POST['snack_fixed'] ?? 20);

        if ($beverageQty < 1 || $beveragePercent < 0 || $beveragePercent > 100 ||
            $snackQty < 1 || $snackFixed < 0) {
            respond_json(['error' => '輸入值無效'], 422);
            return;
        }

        $stmt = $db->prepare('UPDATE promotion_config SET config_value = ?, updated_by = ?, updated_at = NOW() WHERE config_type = "bundle" AND config_key = ?');

        $beverageQtyStr = (string)$beverageQty;
        $beveragePercentStr = (string)$beveragePercent;
        $snackQtyStr = (string)$snackQty;
        $snackFixedStr = (string)$snackFixed;

        $updates = [
            ['beverage_discount_qty', $beverageQtyStr],
            ['beverage_discount_percent', $beveragePercentStr],
            ['snack_discount_qty', $snackQtyStr],
            ['snack_discount_fixed', $snackFixedStr]
        ];

        foreach ($updates as [$key, $value]) {
            $stmt->bind_param('sis', $value, $memberId, $key);
            if (!$stmt->execute()) {
                throw new Exception('Failed to update bundle config');
            }
        }

        $db->commit();
        respond_json([
            'success' => true,
            'message' => '組合優惠設定已更新',
            'updated_config' => [
                'beverage_qty' => $beverageQty,
                'beverage_percent' => $beveragePercent,
                'snack_qty' => $snackQty,
                'snack_fixed' => $snackFixed
            ]
        ]);
    } catch (Throwable $e) {
        $db->rollback();
        respond_json(['error' => 'Update failed: ' . $e->getMessage()], 500);
    }
}
