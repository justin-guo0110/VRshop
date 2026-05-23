<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$currentUser = $_SESSION['user'] ?? null;
 //var_dump($_SESSION['user']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VR Shopping Mall</title>
    <link rel="stylesheet" href="../public/css/style.css">
    <link rel="stylesheet" href="../public/css/enhance.css?v=<?php echo filemtime(__DIR__ . '/../public/css/enhance.css'); ?>">
    <link rel="stylesheet" href="../public/css/cart.css?v=<?php echo filemtime(__DIR__ . '/../public/css/cart.css'); ?>">
    <link rel="stylesheet" href="../public/css/checkout.css?v=<?php echo filemtime(__DIR__ . '/../public/css/checkout.css'); ?>">
    <link rel="stylesheet" href="../public/css/profile.css?v=<?php echo filemtime(__DIR__ . '/../public/css/profile.css'); ?>">
</head>
<body>
    <header class="site-header">
        <div class="container header-inner">
            <?php if ($currentUser && ($currentUser['role'] ?? '') === 'admin'): ?>
                <span class="logo" style="text-decoration: none;">VR Shopping Mall</span>
            <?php else: ?>
                <a href="./index.php" class="logo" style="text-decoration: none;">VR Shopping Mall</a>
            <?php endif; ?>
            <nav class="nav">
                <?php if (!($currentUser && ($currentUser['role'] ?? '') === 'admin')): ?>
                    <a href="../views/index.php">首頁</a>
                    <a href="../views/products.php">商品</a>

                    <?php if ($currentUser): ?>
                        <a href="../views/cart.php">購物車</a>
                        <?php
                            $memberId = $currentUser['member_id'] ?? 0;
                            echo "<!-- debug member_id: " . $memberId . " -->"; // 在瀏覽器原始碼看
                            $vrUrl = "vrmall://launch?member_id=" . $memberId;
                        ?>
                        <a class="btn btn-vr" href="<?php echo $vrUrl; ?>">🥽 進入 VR 商城</a>
                    <?php endif; ?>
                <?php endif; ?>
            </nav>
            <div class="user-info">
                <?php if ($currentUser && ($currentUser['role'] ?? '') === 'admin'): ?>
                    <div class="nav-dropdown admin-dropdown">
                        <a href="#" class="nav-dropdown-toggle">⚙️ 管理</a>
                        <div class="nav-dropdown-menu">
                            <a href="../views/admin.php?page=dashboard">📊 銷售看板</a>
                            <a href="../views/admin.php?page=products">📦 商品管理</a>
                            <a href="../views/admin.php?page=orders">📋 訂單管理</a>
                            <a href="../views/admin.php?page=refunds">↩️ 退單審核</a>
                            <a href="../views/admin.php?page=inventory">📚 庫存管理</a>
                            <a href="../views/admin.php?page=featured_products">⭐ 熱門商品管理</a>
                            <a href="../views/admin.php?page=sidebar_ads">🖼️ 側邊廣告管理</a>
                            <a href="../views/admin.php?page=customers">👥 客戶管理</a>
                            <a href="../views/admin.php?page=promotions">🎁 促銷管理</a>
                            <a href="../views/admin.php?page=chat">💬 客服系統</a>
                        </div>
                    </div>
                <?php endif; ?>
                <?php if ($currentUser && ($currentUser['role'] ?? '') !== 'admin'): ?>
                    <div class="nav-dropdown user-welcome-dropdown" id="userWelcomeDropdown">
                        <button type="button" class="nav-dropdown-toggle welcome-toggle" id="userWelcomeToggle" aria-expanded="false" aria-controls="userWelcomeMenu">
                            歡迎 <?php echo htmlspecialchars($currentUser['name'] ?? $currentUser['email']); ?>
                            <span class="notification-badge" id="headerNotificationBadge" hidden></span>
                            <span style="font-size:11px;opacity:.9;">▼</span>
                        </button>
                        <div class="nav-dropdown-menu user-dropdown-menu" id="userWelcomeMenu">
                            <a href="../views/profile.php">個人資料</a>
                            <a href="../views/notifications.php">通知中心<span class="notification-badge" id="notificationsLinkBadge" hidden></span></a>
                            <a href="../views/coupons.php">我的優惠券</a>
                            <?php if (($currentUser['role'] ?? '') !== 'admin'): ?>
                                <a href="../views/orders.php">我的訂單</a>
                            <?php endif; ?>
                            <div class="coupon-dropdown-block">
                                <div class="coupon-dropdown-title">我的優惠券</div>
                                <div id="headerCouponList" class="coupon-dropdown-list">
                                    <p class="coupon-dropdown-loading">載入中...</p>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                <?php if ($currentUser): ?>
                    <button class="btn btn-secondary" id="logoutBtn" type="button">登出</button>
                <?php else: ?>
                    <a class="btn" href="../views/login.php">登入</a>
                <?php endif; ?>
            </div>
        </div>
    </header>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const adminDropdown = document.querySelector('.nav-dropdown.admin-dropdown');
            if (!adminDropdown) return;

            const toggle = adminDropdown.querySelector('.nav-dropdown-toggle');
            if (!toggle) return;

            toggle.addEventListener('click', function(event) {
                event.preventDefault();
                event.stopPropagation();
                adminDropdown.classList.toggle('open');
            });

            document.addEventListener('click', function(event) {
                if (!adminDropdown.contains(event.target)) {
                    adminDropdown.classList.remove('open');
                }
            });

            document.addEventListener('keydown', function(event) {
                if (event.key === 'Escape') {
                    adminDropdown.classList.remove('open');
                }
            });
        });
    </script>
    <main class="container">

   
