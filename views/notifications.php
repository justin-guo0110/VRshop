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

<section class="card orders-page">
    <div class="orders-head">
        <div>
            <h2>通知中心</h2>
            <p class="orders-subtitle">您可以查看系統公告與訂單相關通知。</p>
        </div>
        <a class="btn btn-secondary" href="./orders.php">我的訂單</a>
    </div>
    <div id="notificationsList" class="orders-list"></div>
</section>

<?php require_once __DIR__ . '/layout_footer.php'; ?>
