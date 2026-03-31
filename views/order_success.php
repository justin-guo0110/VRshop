<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user'])) {
    header('Location: ./login.php');
    exit;
}
?>
<?php require_once __DIR__ . '/layout_header.php'; ?>
<link rel="stylesheet" href="../public/css/orders.css?v=<?php echo filemtime(__DIR__ . '/../public/css/orders.css'); ?>">
<link rel="stylesheet" href="../public/css/order_success.css?v=<?php echo filemtime(__DIR__ . '/../public/css/order_success.css'); ?>">

<section class="card order-success-page">
    <div class="order-success-hero">
        <div class="order-success-badge">完成下單！</div>
        <h2>感謝你的訂購，訂單已成功建立</h2>
        <p>我們已收到你的訂單，接下來會盡快安排出貨。</p>
    </div>

    <div class="order-success-meta">
        <p>訂單編號：<strong id="orderSuccessOrderId">#--</strong></p>
    </div>

    <div id="orderSuccessDetail" class="order-success-detail">
        <div class="order-detail-card">明細載入中...</div>
    </div>

    <div class="order-success-actions">
        <a class="btn btn-secondary" href="./products.php">繼續購物</a>
        <a class="btn" id="viewOrdersBtn" href="./orders.php">前往我的訂單</a>
    </div>
</section>

<?php require_once __DIR__ . '/layout_footer.php'; ?>
