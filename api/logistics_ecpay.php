<?php
require_once __DIR__ . '/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$config = require __DIR__ . '/../config/logistics.php';
$ecpay = $config['ecpay'] ?? [];

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'select_store':
        require_login();
        select_store($ecpay);
        break;
    case 'callback':
        callback_store($ecpay);
        break;
    case 'get_selected_store':
        require_login();
        get_selected_store();
        break;
    case 'clear_selected_store':
        require_login();
        clear_selected_store();
        break;
    default:
        respond_json(['error' => 'Unknown action'], 400);
}

function get_base_url(): string {
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['SERVER_PORT'] ?? '') === '443');
    $scheme = $isHttps ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    return $scheme . '://' . $host;
}

function get_ecpay_endpoint(array $ecpay): string {
    $env = strtolower((string)($ecpay['env'] ?? 'sandbox'));
    if ($env === 'production') {
        return 'https://logistics.ecpay.com.tw/Express/map';
    }
    return 'https://logistics-stage.ecpay.com.tw/Express/map';
}

function ecpay_url_encode(string $value): string {
    $encoded = urlencode($value);
    $search = ['%2D', '%5F', '%2E', '%21', '%2A', '%28', '%29', '%20'];
    $replace = ['-', '_', '.', '!', '*', '(', ')', '+'];
    return str_replace($search, $replace, $encoded);
}

function ecpay_check_mac(array $params, string $hashKey, string $hashIv): string {
    unset($params['CheckMacValue']);
    ksort($params);
    $pairs = [];
    foreach ($params as $key => $value) {
        $pairs[] = $key . '=' . (string)$value;
    }
    $raw = 'HashKey=' . $hashKey . '&' . implode('&', $pairs) . '&HashIV=' . $hashIv;
    $encoded = strtolower(ecpay_url_encode($raw));
    return strtoupper(md5($encoded));
}

function build_trade_no(): string {
    return 'VRS' . date('YmdHis') . random_int(1000, 9999);
}

function select_store(array $ecpay): void {
    $brand = strtoupper(trim((string)($_GET['brand'] ?? $_POST['brand'] ?? '')));
    $subTypeMap = [
        'UNIMART' => 'UNIMARTC2C',
        'FAMI' => 'FAMIC2C',
    ];
    if (!isset($subTypeMap[$brand])) {
        respond_json(['error' => 'Unsupported brand'], 422);
    }

    $merchantId = trim((string)($ecpay['merchant_id'] ?? ''));
    $hashKey = trim((string)($ecpay['hash_key'] ?? ''));
    $hashIv = trim((string)($ecpay['hash_iv'] ?? ''));
    if ($merchantId === '' || $hashKey === '' || $hashIv === '') {
        header('Content-Type: text/html; charset=utf-8');
        echo '<!doctype html><html><head><meta charset="utf-8"><title>綠界設定未完成</title></head><body style="font-family:Arial,sans-serif;padding:24px;line-height:1.7">';
        echo '<h2>綠界物流尚未設定完成</h2>';
        echo '<p>請先設定 <code>ECPAY_MERCHANT_ID</code>、<code>ECPAY_HASH_KEY</code>、<code>ECPAY_HASH_IV</code>。</p>';
        echo '<p>若已設定仍無法開啟門市地圖，通常是商店帳號尚未開通物流服務。</p>';
        echo '<p><a href="../views/checkout.php">返回結帳頁</a></p>';
        echo '</body></html>';
        exit;
    }

    $scriptDir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/api')), '/');
    $callbackUrl = get_base_url() . $scriptDir . '/logistics_ecpay.php?action=callback';

    $params = [
        'MerchantID' => $merchantId,
        'MerchantTradeNo' => build_trade_no(),
        'LogisticsType' => 'CVS',
        'LogisticsSubType' => $subTypeMap[$brand],
        'IsCollection' => 'N',
        'ServerReplyURL' => $callbackUrl,
        'ExtraData' => $brand,
        'Device' => '0',
    ];
    $params['CheckMacValue'] = ecpay_check_mac($params, $hashKey, $hashIv);

    $endpoint = get_ecpay_endpoint($ecpay);

    header('Content-Type: text/html; charset=utf-8');
    echo '<!doctype html><html><head><meta charset="utf-8"><title>Redirecting...</title></head><body>';
    echo '<form id="ecpayStoreForm" method="post" action="' . htmlspecialchars($endpoint, ENT_QUOTES, 'UTF-8') . '">';
    foreach ($params as $key => $value) {
        echo '<input type="hidden" name="' . htmlspecialchars($key, ENT_QUOTES, 'UTF-8') . '" value="' . htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8') . '">';
    }
    echo '</form>';
    echo '<p>正在導向超商門市地圖，請稍候...</p>';
    echo '<script>document.getElementById("ecpayStoreForm").submit();</script>';
    echo '</body></html>';
    exit;
}

function callback_store(array $ecpay): void {
    $merchantId = trim((string)($ecpay['merchant_id'] ?? ''));
    $hashKey = trim((string)($ecpay['hash_key'] ?? ''));
    $hashIv = trim((string)($ecpay['hash_iv'] ?? ''));

    $postedMac = (string)($_POST['CheckMacValue'] ?? '');
    if ($postedMac !== '' && $merchantId !== '' && $hashKey !== '' && $hashIv !== '') {
        $calculated = ecpay_check_mac($_POST, $hashKey, $hashIv);
        if (!hash_equals($calculated, $postedMac)) {
            header('Content-Type: text/plain; charset=utf-8');
            echo 'CheckMacValue invalid';
            exit;
        }
    }

    $subType = strtoupper((string)($_POST['LogisticsSubType'] ?? ''));
    $brand = '超商';
    if (strpos($subType, 'UNIMART') !== false) {
        $brand = '7-ELEVEN';
    } elseif (strpos($subType, 'FAMI') !== false) {
        $brand = '全家';
    } elseif (!empty($_POST['ExtraData'])) {
        $brand = strtoupper((string)$_POST['ExtraData']);
    }

    $picked = [
        'brand' => $brand,
        'store_id' => (string)($_POST['CVSStoreID'] ?? ''),
        'store_name' => (string)($_POST['CVSStoreName'] ?? ''),
        'store_address' => (string)($_POST['CVSAddress'] ?? ''),
        'sub_type' => (string)($_POST['LogisticsSubType'] ?? ''),
        'updated_at' => time(),
    ];
    $_SESSION['pickup_store'] = $picked;

    $qs = http_build_query([
        'pickup_selected' => 1,
        'pickup_brand' => $picked['brand'],
        'pickup_store_id' => $picked['store_id'],
        'pickup_store_name' => $picked['store_name'],
        'pickup_store_address' => $picked['store_address'],
    ]);
    header('Location: ../views/checkout.php?' . $qs);
    exit;
}

function get_selected_store(): void {
    $store = $_SESSION['pickup_store'] ?? null;
    respond_json(['success' => true, 'store' => $store]);
}

function clear_selected_store(): void {
    unset($_SESSION['pickup_store']);
    respond_json(['success' => true]);
}
