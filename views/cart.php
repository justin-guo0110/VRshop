<?php require_once __DIR__ . '/layout_header.php'; ?>
<section class="card">
    <h2>購物車</h2>
    <div id="cartItems"></div>
    <div id="cartSummary">
        <p>總計：<strong id="cartTotalPrice">$0</strong></p>
        <p>已選商品：<strong id="selectedCount">0</strong> 件</p>
        <p>已選金額：<strong id="cartSelectedPrice">$0</strong></p>
    </div>
    <div class="actions">
        <button class="btn btn-secondary" id="clearCartBtn">清空購物車</button>
<<<<<<< HEAD
        <a class="btn" href="../views/checkout.php">前往結帳</a>
=======
        <button class="btn" id="checkoutSelectedBtn" disabled>前往結帳（請先勾選商品）</button>
>>>>>>> 3a26cb803e8a6afb233c9a406866f29a24b238b8
    </div>
</section>
<?php require_once __DIR__ . '/layout_footer.php'; ?>
