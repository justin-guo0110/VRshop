<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user'])) {
    header('Location: /project/views/login.php');
    exit;
}
?>

<?php require_once __DIR__ . '/layout_header.php'; ?>


<div class="checkout-container">
    <!-- 主要結帳流程 -->
    <div class="checkout-main">
        <!-- 1. 購物車商品 -->
        <section class="card">
            <h2>📦 購物車商品</h2>
            <div class="selection-hint">
                💡 選擇要結帳的商品（未勾選的將不計入訂單）
            </div>
            <div class="checkout-items-list" id="checkoutCart"></div>
        </section>

        <!-- 2. 送貨地址 -->
        <section class="card">
            <h3>📍 選擇送貨地址</h3>
            <div class="address-options" id="checkoutAddresses">
                <p>載入中...</p>
            </div>
            <a class="btn" href="./profile.php#addressSection" style="margin-top: 12px; width: 100%; text-align: center; display: block;">+ 新增地址</a>
        </section>

        <!-- 3. 送貨方式 -->
        <section class="card">
            <h3>🚚 選擇送貨方式</h3>
            <div class="options-group">
                <label class="option-item">
                    <input type="radio" name="shipping_method" value="宅配" checked>
                    <span><strong>宅配</strong> - 3~5 個工作天</span>
                </label>
                <label class="option-item">
                    <input type="radio" name="shipping_method" value="超商取貨">
                    <span><strong>超商取貨</strong> - 2~3 個工作天</span>
                </label>
            </div>
            <p style="margin: 12px 0 0; color: var(--muted); font-size: 0.9rem;">
                💡 提示：部分商品可能不支援超商取貨。
            </p>
        </section>

        <!-- 4. 支付方式 -->
        <section class="card">
            <h3>💳 選擇支付方式</h3>
            <div class="options-group">
                <label class="option-item">
                    <input type="radio" name="payment_method" value="信用卡" checked>
                    <span><strong>信用卡</strong> - 立即支付</span>
                </label>
                <label class="option-item">
                    <input type="radio" name="payment_method" value="貨到付款">
                    <span><strong>貨到付款</strong> - 送達時付款</span>
                </label>
            </div>
            <p style="margin: 12px 0 0; color: var(--muted); font-size: 0.9rem;">
                💡 提示：貨到付款可能會有額外手續費。
            </p>
        </section>

        <!-- 5. 確認下單 -->
        <section class="card">
            <div class="checkout-actions">
                <button class="btn btn-place-order" id="placeOrderBtn">確認下單</button>
            </div>
            <div class="message" id="checkoutMessage"></div>
        </section>
    </div>

    <!-- 右側結帳摘要 -->
    <aside class="checkout-summary">
        <section class="card" id="checkoutTotals" style="display: none;">
            <h3 style="margin: 0 0 16px 0; border: none;">💰 訂單摘要</h3>
            <div class="totals-section">
                <div class="total-row">
                    <span>商品小計</span>
                    <strong id="checkoutSubtotal">$0</strong>
                </div>
                <div class="total-row">
                    <span>送貨費</span>
                    <strong id="checkoutShippingFee">$0</strong>
                </div>
                <div class="total-row">
                    <span>支付手續費</span>
                    <strong id="checkoutPaymentFee">$0</strong>
                </div>
                <div class="total-row grand-total">
                    <span>應付總額</span>
                    <strong id="checkoutGrandTotal">$0</strong>
                </div>
                <p id="checkoutEta" style="margin: 12px 0 0; color: var(--muted); font-size: 0.85rem; text-align: center;"></p>
            </div>
        </section>
    </aside>
</div>

<?php require_once __DIR__ . '/layout_footer.php'; ?>


<script>
function loadCheckoutCart() {
    fetch('/project/api/cart.php?action=get')
        .then(res => res.json())
        .then(data => {
            const box = document.getElementById('checkoutCart');
            if (!data.items || data.items.length === 0) {
                box.innerHTML = "<p>購物車是空的</p>";
                return;
            }

            let html = "<ul>";
            data.items.forEach(item => {
                html += `<li>${item.name} x ${item.quantity} = ${item.quantity * item.price}</li>`;
            });
            html += `</ul><p><strong>總金額：${data.total}</strong></p>`;

            box.innerHTML = html;
        });
}

function loadCheckoutAddresses() {
    fetch('/project/api/member.php?action=list_addresses')
        .then(res => res.json())
        .then(data => {
            const box = document.getElementById('checkoutAddresses');
            if (!data.addresses || data.addresses.length === 0) {
                box.innerHTML = "<p>尚未新增地址，請至會員中心新增。</p>";
                return;
            }

            let html = "";
            data.addresses.forEach(addr => {
                html += `
                    <label style="display:block;margin-bottom:6px;">
                        <input type="radio" name="address_id" value="${addr.address_id}">
                        ${addr.recipient_name}｜${addr.phone}｜${addr.address_line}
                    </label>
                `;
            });

            box.innerHTML = html;
        });
}

document.getElementById('placeOrderBtn').addEventListener('click', function () {
    const shipping_method = document.querySelector('input[name="shipping_method"]:checked').value;
    const payment_method  = document.querySelector('input[name="payment_method"]:checked').value;
    const addressRadio    = document.querySelector('input[name="address_id"]:checked');

    if (!addressRadio) {
        alert("請先選擇送貨地址");
        return;
    }

    const formData = new FormData();
    formData.append("shipping_method", shipping_method);
    formData.append("payment_method", payment_method);
    formData.append("address_id", addressRadio.value);

    fetch('/project/api/orders.php?action=place_order', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.error) {
            alert("下單失敗：" + data.error);
            return;
        }

        alert("訂單建立成功！訂單編號：" + data.order_id);

        window.location.href = '/project/views/orders.php';
    })
    .catch(err => {
        alert("發生錯誤：" + err);
    });
});

loadCheckoutCart();
loadCheckoutAddresses();
</script>
