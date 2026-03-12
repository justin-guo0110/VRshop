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

<section class="card checkout-section">
    <h2>結帳</h2>
    <div id="checkoutCart"></div>
    <div id="checkoutTotals" style="margin-top:12px;display:none;">
        <div style="display:flex;justify-content:space-between;gap:12px;margin:6px 0;">
            <span style="color:#666;">商品小計</span>
            <strong id="checkoutSubtotal">$0</strong>
        </div>
        <div style="display:flex;justify-content:space-between;gap:12px;margin:6px 0;">
            <span style="color:#666;">運費</span>
            <strong id="checkoutShippingFee">$0</strong>
        </div>
        <div style="display:flex;justify-content:space-between;gap:12px;margin:6px 0;">
            <span style="color:#666;">支付手續費</span>
            <strong id="checkoutPaymentFee">$0</strong>
        </div>
        <hr style="border:0;border-top:1px solid #e4e7ec;margin:10px 0;">
        <div style="display:flex;justify-content:space-between;gap:12px;margin:6px 0;align-items:baseline;">
            <span style="font-weight:800;">應付總額</span>
            <span style="font-size:1.2rem;font-weight:900;color:#ff7a18;" id="checkoutGrandTotal">$0</span>
        </div>
        <p id="checkoutEta" style="margin:10px 0 0;color:#666;"></p>
    </div>
</section>

<section class="card checkout-section">
    <h3>選擇送貨地址</h3>
    <div id="checkoutAddresses">載入中...</div>
</section>

<section class="card checkout-section">
    <h3>選擇送貨方式</h3>
    <label><input type="radio" name="shipping_method" value="宅配" checked> 宅配</label>
    <label><input type="radio" name="shipping_method" value="超商取貨"> 超商取貨</label>
    <p style="margin:8px 0 0;color:#666;">提示：部分商品可能不支援超商取貨。</p>
</section>

<section class="card checkout-section">
    <h3>選擇支付方式</h3>
    <label><input type="radio" name="payment_method" value="信用卡" checked> 信用卡</label>
    <label><input type="radio" name="payment_method" value="貨到付款"> 貨到付款</label>
    <p style="margin:8px 0 0;color:#666;">提示：貨到付款可能會有額外手續費。</p>
</section>

<section class="card checkout-section">
    <button class="btn" id="placeOrderBtn">確認下單</button>
    <div class="message" id="checkoutMessage"></div>
</section>

<?php require_once __DIR__ . '/layout_footer.php'; ?>
