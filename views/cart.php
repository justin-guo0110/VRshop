<?php require_once __DIR__ . '/layout_header.php'; ?>
<section class="card">
    <h2>購物車</h2>
    <div id="cartItems"></div>
    <div id="cartSummary">
        <p>總計：<strong id="cartTotalPrice">$0</strong></p>
    </div>
    <div class="actions">
        <button class="btn btn-secondary" id="clearCartBtn">清空購物車</button>
        <a class="btn" href="./checkout.php">前往結帳</a>
    </div>
</section>
<?php require_once __DIR__ . '/layout_footer.php'; ?>
