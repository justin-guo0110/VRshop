<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$currentUser = $_SESSION['user'] ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VR Mall</title>
    <link rel="stylesheet" href="../public/css/style.css">
    <link rel="stylesheet" href="../public/css/enhance.css?v=<?php echo filemtime(__DIR__ . '/../public/css/enhance.css'); ?>">
</head>
<body>
    <header class="site-header">
        <div class="container header-inner">
            <a href="./index.php" class="logo" style="text-decoration: none;">VR Mall</a>
            <nav class="nav">
                <?php if ($currentUser && ($currentUser['role'] ?? '') === 'admin'): ?>
                    <a href="../views/admin.php?page=dashboard">📊 儀表板</a>
                    <a href="../views/admin.php?page=orders">📋 訂單管理</a>
                    <a href="../views/admin.php?page=products">📦 商品管理</a>
                    <a href="../views/admin.php?page=inventory">📚 庫存管理</a>
                    <a href="../views/admin.php?page=chat">💬 客服系統</a>
                <?php else: ?>
                    <a href="../views/index.php">首頁</a>
                    <a href="../views/products.php">商品</a>
                    <a href="../views/profile.php">個人資料</a>

                    <?php if ($currentUser): ?>
                        <a href="../views/cart.php">購物車</a>
                        <a href="../views/orders.php">訂單</a>
                    <?php endif; ?>
                <?php endif; ?>
            </nav>
            <div class="user-info">
                <?php if ($currentUser): ?>
                    <span>歡迎 <?php echo htmlspecialchars($currentUser['name'] ?? $currentUser['email']); ?></span>
                    <button class="btn btn-secondary" id="logoutBtn">登出</button>
                <?php else: ?>
                    <a class="btn" href="../views/login.php">登入</a>
                <?php endif; ?>
            </div>
        </div>
    </header>
    <main class="container">
