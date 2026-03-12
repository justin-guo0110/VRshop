<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/../lib/mailer.php';

$action = $_GET['action'] ?? '';
switch ($action) {
    case 'register':
        register();
        break;
    case 'login':
        login();
        break;
    case 'logout':
        logout();
        break;
    case 'me':
        me();
        break;
    case 'request_password_reset':
        request_password_reset();
        break;
    case 'reset_password':
        reset_password();
        break;
    case 'verify_reset_token':
        verify_reset_token();
        break;
    default:
        respond_json(['error' => 'Unknown action'], 400);
}

function register(): void {
    $db = get_db();
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');

    if ($email === '' || $password === '' || $name === '') {
        respond_json(['error' => 'Missing fields'], 422);
    }
    
    // 密碼強度驗證
    if (strlen($password) < 8) {
        respond_json(['error' => '密碼長度至少需要 8 個字符'], 422);
    }

    $stmt = $db->prepare('SELECT member_id FROM members WHERE email = ?');
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        respond_json(['error' => 'Email already registered'], 409);
    }
    $stmt->close();

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $db->prepare('INSERT INTO members (email, password_hash, name, phone, role) VALUES (?, ?, ?, ?, "member")');
    $stmt->bind_param('ssss', $email, $hash, $name, $phone);
    if ($stmt->execute()) {
        $_SESSION['user'] = [
            'member_id' => $stmt->insert_id,
            'email' => $email,
            'name' => $name,
            'phone' => $phone,
            'role' => 'member'
        ];
        respond_json(['success' => true, 'user' => $_SESSION['user']]);
    }
    respond_json(['error' => 'Registration failed'], 500);
}

function login(): void {
    $db = get_db();
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    if ($email === '' || $password === '') {
        respond_json(['error' => 'Missing credentials'], 422);
    }
    $stmt = $db->prepare('SELECT member_id, email, password_hash, name, phone, role FROM members WHERE email = ?');
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $res = $stmt->get_result();
    $user = $res->fetch_assoc();
    if ($user && (password_verify($password, $user['password_hash']) || hash_equals(hash('sha256', $password), $user['password_hash']))) {
        unset($user['password_hash']);
        $_SESSION['user'] = $user;
        respond_json(['success' => true, 'user' => $user]);
    }
    respond_json(['error' => 'Invalid credentials'], 401);
}

function logout(): void {
    session_destroy();
    respond_json(['success' => true]);
}

function me(): void {
    $user = current_user();
    if ($user) {
        respond_json(['user' => $user]);
    }
    respond_json(['user' => null]);
}

function request_password_reset(): void {
    $email = trim($_POST['email'] ?? '');
    if ($email === '') {
        respond_json(['error' => 'Email required'], 422);
    }

    $db = get_db();
    $stmt = $db->prepare('SELECT member_id, name FROM members WHERE email = ? LIMIT 1');
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    if (!$user) {
        // 避免暴露帳號是否存在，同樣回傳成功
        respond_json(['success' => true, 'message' => '若帳號存在，重設連結已寄出，請查收您的信箱。']);
    }

    $token = bin2hex(random_bytes(32));
    $expires = date('Y-m-d H:i:s', time() + 1800); // 30 分鐘

    $delete = $db->prepare('DELETE FROM password_resets WHERE member_id = ?');
    $delete->bind_param('i', $user['member_id']);
    $delete->execute();

    $insert = $db->prepare('INSERT INTO password_resets (member_id, token, expires_at) VALUES (?, ?, ?)');
    $insert->bind_param('iss', $user['member_id'], $token, $expires);
    $insert->execute();

    $resetUrl = build_reset_url($token);
    $subject = 'VR Mall 密碼重設';
    $name = $user['name'] ?? $email;
    $html = "<p>{$name} 您好：</p><p>請在 30 分鐘內點擊下方連結重設密碼：</p><p><a href=\"{$resetUrl}\">{$resetUrl}</a></p><p>若您未發出此請求，請忽略此信。</p>";
    $text = "{$name} 您好：\n請在 30 分鐘內開啟以下連結重設密碼：\n{$resetUrl}\n若您未發出此請求，請忽略此信。";

    // 試著寄送郵件，如果失敗則回傳錯誤給前端
    $mailResult = send_mail($email, $subject, $html, $text);
    if (!$mailResult['success']) {
        // 記錄詳細原因，方便開發或運維檢查
        error_log('Mail send failed: ' . ($mailResult['message'] ?? 'unknown'));
        respond_json([
            'success' => false,
            'error' => '無法寄出重設信件，請聯絡管理員或稍後再試。',
            'details' => $mailResult['message'] ?? ''
        ], 500);
    }

    respond_json(['success' => true, 'message' => '已寄出重設連結，如未收到請檢查垃圾信件匣。']);
}

function verify_reset_token(): void {
    $token = trim($_GET['token'] ?? '');
    if ($token === '') {
        respond_json(['valid' => false, 'error' => 'Token required'], 422);
    }

    $db = get_db();
    $now = date('Y-m-d H:i:s');
    $stmt = $db->prepare('SELECT member_id, expires_at FROM password_resets WHERE token = ? AND expires_at >= ? LIMIT 1');
    $stmt->bind_param('ss', $token, $now);
    $stmt->execute();
    $record = $stmt->get_result()->fetch_assoc();
    
    if ($record) {
        respond_json(['valid' => true, 'expires_at' => $record['expires_at']]);
    } else {
        // 檢查是否過期
        $stmt2 = $db->prepare('SELECT expires_at FROM password_resets WHERE token = ? LIMIT 1');
        $stmt2->bind_param('s', $token);
        $stmt2->execute();
        $expired = $stmt2->get_result()->fetch_assoc();
        if ($expired) {
            respond_json(['valid' => false, 'error' => 'Token 已過期，請重新申請重設密碼']);
        } else {
            respond_json(['valid' => false, 'error' => 'Token 不存在或無效']);
        }
    }
}

function reset_password(): void {
    $token = trim($_POST['token'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if ($token === '' || $password === '') {
        respond_json(['error' => 'Token and password are required'], 422);
    }
    
    // 密碼強度驗證
    if (strlen($password) < 8) {
        respond_json(['error' => '密碼長度至少需要 8 個字符'], 422);
    }
    
    if ($password !== $confirm) {
        respond_json(['error' => 'Passwords do not match'], 422);
    }

    $db = get_db();
    $now = date('Y-m-d H:i:s');
    $stmt = $db->prepare('SELECT member_id FROM password_resets WHERE token = ? AND expires_at >= ? LIMIT 1');
    $stmt->bind_param('ss', $token, $now);
    $stmt->execute();
    $record = $stmt->get_result()->fetch_assoc();
    if (!$record) {
        respond_json(['error' => 'Token 已失效或不存在'], 404);
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $update = $db->prepare('UPDATE members SET password_hash = ? WHERE member_id = ?');
    $update->bind_param('si', $hash, $record['member_id']);
    if (!$update->execute()) {
        respond_json(['error' => 'Failed to update password'], 500);
    }

    $cleanup = $db->prepare('DELETE FROM password_resets WHERE member_id = ?');
    $cleanup->bind_param('i', $record['member_id']);
    $cleanup->execute();

    respond_json(['success' => true, 'message' => '密碼已更新，請使用新密碼登入。']);
}

function build_reset_url(string $token): string
{
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $basePath = dirname(dirname($_SERVER['SCRIPT_NAME'] ?? '/')) ?: '/';
    $basePath = rtrim($basePath, '/') . '/';

    // 確保空白轉為 %20，避免資料夾名稱包含空格時連結失效
    $basePath = str_replace(' ', '%20', $basePath);

    return $scheme . $host . $basePath . 'views/reset_password.php?token=' . urlencode($token);
}
