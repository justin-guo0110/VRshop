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
            <h2>訂單查詢</h2>
            <p class="orders-subtitle">檢視歷史訂單、配送狀態與明細內容。</p>
        </div>
        <a class="btn btn-secondary" href="./products.php">繼續購物</a>
    </div>
    <div class="orders-filters" id="ordersFilters" aria-label="訂單分類">
        <button type="button" class="orders-filter-btn is-active" data-status="all">全部</button>
        <button type="button" class="orders-filter-btn" data-status="pending">待確認</button>
        <button type="button" class="orders-filter-btn" data-status="accepted">已接單</button>
        <button type="button" class="orders-filter-btn" data-status="refund_pending">退單審核中</button>
        <button type="button" class="orders-filter-btn" data-status="preparing">備貨中</button>
        <button type="button" class="orders-filter-btn" data-status="shipping">運送中</button>
        <button type="button" class="orders-filter-btn" data-status="done">已完成</button>
        <button type="button" class="orders-filter-btn" data-status="cancelled">已取消</button>
    </div>
    <div id="ordersList" class="orders-list"></div>
</section>
<?php require_once __DIR__ . '/layout_footer.php'; ?>
