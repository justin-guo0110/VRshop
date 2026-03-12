<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$currentUser = $_SESSION['user'] ?? null;
$pageTitle = $pageTitle ?? 'VR Mall';
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <meta name="theme-color" content="#ff7a18">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700&family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../public/css/style.css">
</head>
<body>
    <header class="site-header">
        <div class="container header-inner">
            <a href="./index.php" class="logo" style="text-decoration: none;">VR Mall</a>
            <nav class="nav">
                <a href="./index.php">首頁</a>
                <a href="./products.php">商品</a>
                <a href="./profile.php">個人資料</a>
                
                <?php if ($currentUser && ($currentUser['role'] ?? '') === 'member'): ?>
                    <a href="./cart.php">購物車</a>
                    <a href="./orders.php">訂單</a>
                <?php endif; ?>

                <?php if ($currentUser && ($currentUser['role'] ?? '') === 'admin'): ?>
                    <a href="./admin.php">管理</a>
                <?php endif; ?>
            </nav>
            <div class="user-info">
                <?php if ($currentUser): ?>
                    <span>歡迎 <?php echo htmlspecialchars($currentUser['name'] ?? $currentUser['email']); ?></span>
                    <button class="btn btn-secondary" id="logoutBtn">登出</button>
                <?php else: ?>
                    <a class="btn" href="./login.php">登入</a>
                <?php endif; ?>
            </div>
        </div>
    </header>
    <main class="container">
