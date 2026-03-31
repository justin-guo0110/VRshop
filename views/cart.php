<?php require_once __DIR__ . '/layout_header.php'; ?>

<section class="card checkout-progress-card" aria-label="結帳流程進度">
    <ol class="checkout-progress" role="list">
        <li class="checkout-progress-step is-active">
            <span class="step-dot">1</span>
            <div class="step-text">
                <strong>選擇要購買的商品</strong>
                <small>於購物車勾選商品與數量</small>
            </div>
        </li>
        <li class="checkout-progress-step">
            <span class="step-dot">2</span>
            <div class="step-text">
                <strong>填寫配送與付款</strong>
                <small>地址、配送、付款方式</small>
            </div>
        </li>
        <li class="checkout-progress-step">
            <span class="step-dot">3</span>
            <div class="step-text">
                <strong>完成下單</strong>
                <small>送出訂單並查看結果</small>
            </div>
        </li>
    </ol>
</section>

<section class="card cart-page">
    <h2>購物車</h2>
    <div id="cartItems" class="cart-items"></div>
    <div id="cartSummary">
        <p>總計：<strong id="cartTotalPrice">$0</strong></p>
        <p>已選商品：<strong id="selectedCount">0</strong> 件</p>
        <p>已選金額：<strong id="cartSelectedPrice">$0</strong></p>
    </div>
    <div class="actions">
        <button class="btn btn-secondary" id="clearCartBtn">清空購物車</button>
        <button class="btn" id="checkoutSelectedBtn" disabled>前往結帳（請先勾選商品）</button>
    </div>
</section>

<?php require_once __DIR__ . '/layout_footer.php'; ?>
