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


<div class="checkout-page">
    <div class="checkout-layout">
        <div class="checkout-main">
            <section class="card checkout-section">
                <h2>結帳</h2>
                <p class="checkout-note">請確認本次要結帳的商品與配送資訊。</p>
                <div class="checkout-items-list" id="checkoutCart"></div>
            </section>

            <section class="card checkout-section">
                <h3>選擇送貨地址</h3>
                <div class="address-options" id="checkoutAddresses">
                    <p>載入中...</p>
                </div>
                <a class="btn add-address-btn" href="./profile.php#addressSection">+ 新增地址</a>
            </section>

            <section class="card checkout-section">
                <h3>選擇送貨方式</h3>
                <div class="options-group">
                    <label class="option-item">
                        <input type="radio" name="shipping_method" value="宅配" checked>
                        <span><strong>宅配</strong><small>3~5 個工作天</small></span>
                    </label>

                    <label class="option-item">
                        <input type="radio" name="shipping_method" value="超商取貨">
                        <span><strong>超商取貨</strong><small>2~3 個工作天</small></span>
                    </label>
                </div>
                <p class="checkout-tip">部分商品可能不支援超商取貨。</p>
            </section>

            <section class="card checkout-section">
                <h3>選擇支付方式</h3>
                <div class="options-group">
                    <label class="option-item">
                        <input type="radio" name="payment_method" value="信用卡" checked>
                        <span><strong>信用卡</strong><small>立即支付</small></span>
                    </label>

                    <label class="option-item">
                        <input type="radio" name="payment_method" value="貨到付款">
                        <span><strong>貨到付款</strong><small>送達時付款</small></span>
                    </label>
                </div>
                <p class="checkout-tip">貨到付款可能會有額外手續費。</p>
            </section>

            <section class="card checkout-section">
                <div class="checkout-actions">
                    <button class="btn btn-place-order" id="placeOrderBtn">確認下單</button>
                </div>
                <div class="message" id="checkoutMessage"></div>
            </section>
        </div>

        <aside class="checkout-sidebar">
            <section class="card checkout-summary-card" id="checkoutTotals">
                <h3>訂單摘要</h3>
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
                </div>
                <p id="checkoutEta" class="checkout-eta"></p>
            </section>
        </aside>
    </div>
</div>
<?php require_once __DIR__ . '/layout_footer.php'; ?>


<script>
function loadCheckoutCart() {
    fetch('/project/api/cart.php?action=get')
        .then(res => res.json())
        .then(data => {
            const box = document.getElementById('checkoutCart');

            if (!data.items || data.items.length === 0) {
                box.innerHTML = `<div class="checkout-empty">購物車是空的</div>`;
                return;
            }

            let html = `<div class="checkout-cart-list">`;

            data.items.forEach(item => {
                const itemSubtotal = Number(item.price) * Number(item.quantity);

                html += `
                <div class="checkout-cart-item">

                    <div class="checkout-cart-info">
                        <input type="checkbox" class="checkout-item-check" checked>

                        <img src="${item.image_url}" 
                             class="checkout-cart-image"
                             onerror="this.src='/project/public/images/default.png'">

                        <div class="checkout-cart-text">
                            <p class="checkout-cart-name">${item.name}</p>
                            <p class="checkout-cart-meta">單價：$${Number(item.price).toFixed(2)}</p>
                            <p class="checkout-cart-meta">數量：${item.quantity}</p>
                            <p class="checkout-cart-subtotal">小計：$${itemSubtotal.toFixed(2)}</p>
                        </div>
                    </div>

                    <div class="checkout-cart-actions">
                        <input type="number" value="${item.quantity}" min="1">
                        <button class="btn btn-secondary">更新數量</button>
                        <button class="btn btn-secondary">刪除商品</button>
                    </div>

                </div>
                `;
            });

            html += `</div>`;

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
