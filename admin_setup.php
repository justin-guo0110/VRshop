<?php
/**
 * VR Shopping Mall - 管理員登入配置工具
 * 用於建立和測試管理員帳戶
 */

require_once __DIR__ . '/api/db.php';

session_start();

// 檢查是否已登入
$is_logged_in = isset($_SESSION['user_id']) && isset($_SESSION['role']) && $_SESSION['role'] === 'admin';

// 處理建立新管理員帳戶的請求
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    if ($action === 'create_admin') {
        $email = trim($_POST['email'] ?? '');
        $password = trim($_POST['password'] ?? '');
        $name = trim($_POST['name'] ?? '');
        
        // 驗證輸入
        if (!$email || !$password || !$name) {
            $error = '請填寫所有必填欄位';
        } elseif (strlen($password) < 6) {
            $error = '密碼長度至少 6 位';
        } else {
            // 檢查信箱是否已存在
            $check_stmt = $conn->prepare("SELECT member_id FROM members WHERE email = ?");
            $check_stmt->bind_param("s", $email);
            $check_stmt->execute();
            
            if ($check_stmt->get_result()->num_rows > 0) {
                $error = '該信箱已被使用';
            } else {
                // 建立新管理員
                $password_hash = password_hash($password, PASSWORD_BCRYPT);
                $insert_stmt = $conn->prepare(
                    "INSERT INTO members (email, password_hash, name, role) VALUES (?, ?, ?, 'admin')"
                );
                $insert_stmt->bind_param("sss", $email, $password_hash, $name);
                
                if ($insert_stmt->execute()) {
                    $success = "✅ 管理員帳戶建立成功！<br>信箱: $email";
                } else {
                    $error = '建立失敗: ' . $conn->error;
                }
            }
        }
    }
    
    // 處理登入
    if ($action === 'login') {
        $email = trim($_POST['email'] ?? '');
        $password = trim($_POST['password'] ?? '');
        
        if (!$email || !$password) {
            $error = '請輸入信箱和密碼';
        } else {
            $login_stmt = $conn->prepare(
                "SELECT member_id, password_hash, name, role FROM members WHERE email = ? AND role = 'admin'"
            );
            $login_stmt->bind_param("s", $email);
            $login_stmt->execute();
            $result = $login_stmt->get_result();
            
            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();
                if (password_verify($password, $user['password_hash'])) {
                    $_SESSION['user_id'] = $user['member_id'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['name'] = $user['name'];
                    $is_logged_in = true;
                    $success = "✅ 登入成功！歡迎 {$user['name']}";
                } else {
                    $error = '密碼錯誤';
                }
            } else {
                $error = '信箱或密碼錯誤';
            }
        }
    }
}

// 獲取現有管理員列表
$admins_result = $conn->query("SELECT member_id, email, name, created_at FROM members WHERE role = 'admin' ORDER BY created_at DESC");
$admins = $admins_result->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VR Shopping Mall - 管理員配置</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Nunito', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            max-width: 900px;
            width: 100%;
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .header h1 {
            font-size: 28px;
            margin-bottom: 10px;
        }
        
        .header p {
            font-size: 14px;
            opacity: 0.9;
        }
        
        .content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            padding: 30px;
        }
        
        @media (max-width: 768px) {
            .content {
                grid-template-columns: 1fr;
            }
        }
        
        .card {
            border: 1px solid #eee;
            border-radius: 8px;
            padding: 25px;
        }
        
        .card h2 {
            font-size: 18px;
            margin-bottom: 20px;
            color: #333;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        label {
            display: block;
            font-size: 14px;
            font-weight: 600;
            color: #555;
            margin-bottom: 6px;
        }
        
        input {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            font-family: inherit;
            transition: border-color 0.3s;
        }
        
        input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        button {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }
        
        button:active {
            transform: translateY(0);
        }
        
        .alert {
            padding: 12px 15px;
            border-radius: 6px;
            margin-bottom: 15px;
            font-size: 14px;
        }
        
        .alert-success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        
        .alert-error {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        
        .admin-list {
            background: #f9f9f9;
            border-radius: 6px;
            padding: 15px;
            margin-top: 15px;
        }
        
        .admin-item {
            padding: 10px;
            background: white;
            border-radius: 4px;
            margin-bottom: 8px;
            font-size: 14px;
            border-left: 3px solid #667eea;
        }
        
        .admin-item:last-child {
            margin-bottom: 0;
        }
        
        .admin-email {
            font-weight: 600;
            color: #333;
        }
        
        .admin-date {
            font-size: 12px;
            color: #999;
            margin-top: 4px;
        }
        
        .status {
            display: inline-block;
            padding: 4px 12px;
            background: #e3f2fd;
            color: #1976d2;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            margin-left: 10px;
        }
        
        .login-status {
            background: #fff3cd;
            border: 1px solid #ffc107;
            border-radius: 6px;
            padding: 15px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .login-status.logged-in {
            background: #d4edda;
            border-color: #28a745;
            color: #155724;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>🔐 VR Shopping Mall 管理員配置</h1>
        <p>建立或登入管理員帳戶以進入後台</p>
    </div>
    
    <div class="content">
        <!-- 登入面板 -->
        <div class="card">
            <h2>📝 管理員登入</h2>
            
            <?php if ($is_logged_in): ?>
                <div class="login-status logged-in">
                    ✅ 已登入為: <strong><?php echo htmlspecialchars($_SESSION['name']); ?></strong>
                    <br><br>
                    <a href="views/admin.php" style="color: #155724; text-decoration: none; font-weight: 600;">
                        → 進入基礎後台
                    </a>
                    <br>
                    <a href="views/operations.php" style="color: #155724; text-decoration: none; font-weight: 600;">
                        → 進入營運後台
                    </a>
                </div>
            <?php else: ?>
                <form method="POST">
                    <input type="hidden" name="action" value="login">
                    
                    <?php if (isset($error)): ?>
                        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
                    <?php endif; ?>
                    
                    <div class="form-group">
                        <label for="login_email">信箱</label>
                        <input type="email" id="login_email" name="email" placeholder="admin@example.com" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="login_password">密碼</label>
                        <input type="password" id="login_password" name="password" placeholder="••••••••" required>
                    </div>
                    
                    <button type="submit">🔓 登入</button>
                </form>
                
                <div style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #eee; font-size: 12px; color: #999; text-align: center;">
                    💡 預設管理員: admin@example.com<br>
                    (如果密碼不確定，請建立新帳戶)
                </div>
            <?php endif; ?>
        </div>
        
        <!-- 建立管理員面板 -->
        <div class="card">
            <h2>👤 建立新管理員</h2>
            
            <form method="POST">
                <input type="hidden" name="action" value="create_admin">
                
                <?php if (isset($success) && $action === 'create_admin'): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <div class="form-group">
                    <label for="new_email">信箱</label>
                    <input type="email" id="new_email" name="email" placeholder="admin@vrshop.com" required>
                </div>
                
                <div class="form-group">
                    <label for="new_name">姓名</label>
                    <input type="text" id="new_name" name="name" placeholder="你的名字" required>
                </div>
                
                <div class="form-group">
                    <label for="new_password">密碼 (至少 6 位)</label>
                    <input type="password" id="new_password" name="password" placeholder="••••••••" required>
                </div>
                
                <button type="submit">➕ 建立管理員</button>
            </form>
        </div>
    </div>
    
    <!-- 現有管理員列表 -->
    <div style="padding: 30px; background: #f5f7fa; border-top: 1px solid #e0e0e0;">
        <h3 style="margin-bottom: 15px; color: #333;">📋 現有管理員</h3>
        
        <?php if (count($admins) > 0): ?>
            <div class="admin-list">
                <?php foreach ($admins as $admin): ?>
                    <div class="admin-item">
                        <span class="admin-email"><?php echo htmlspecialchars($admin['email']); ?></span>
                        <span class="status">✓ 管理員</span>
                        <div class="admin-date">
                            👤 <?php echo htmlspecialchars($admin['name']); ?> 
                            · 建立於 <?php echo date('Y-m-d H:i', strtotime($admin['created_at'])); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div style="text-align: center; color: #999; padding: 20px;">
                <p>暫無管理員帳戶</p>
            </div>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
