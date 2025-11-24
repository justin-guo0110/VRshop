<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user'])) {
    header('Location: /test/views/login.php');
    exit;
}
?>

<?php require_once __DIR__ . '/layout_header.php'; ?>

<section class="card checkout-section">
    <h2>結帳</h2>
    <div id="checkoutCart"></div>
</section>

<section class="card checkout-section">
    <h3>選擇送貨地址</h3>
    <div id="checkoutAddresses">載入中...</div>
</section>

<section class="card checkout-section">
    <h3>選擇送貨方式</h3>
    <label><input type="radio" name="shipping_method" value="宅配" checked> 宅配</label>
    <label><input type="radio" name="shipping_method" value="超商取貨"> 超商取貨</label>
</section>

<section class="card checkout-section">
    <h3>選擇支付方式</h3>
    <label><input type="radio" name="payment_method" value="信用卡" checked> 信用卡</label>
    <label><input type="radio" name="payment_method" value="貨到付款"> 貨到付款</label>
</section>

<section class="card checkout-section">
    <button class="btn" id="placeOrderBtn">確認下單</button>
    <div class="message" id="checkoutMessage"></div>
</section>

<?php require_once __DIR__ . '/layout_footer.php'; ?>


<script>
function loadCheckoutCart() {
    fetch('/test/api/cart.php?action=get')
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
    fetch('/test/api/member.php?action=list_addresses')
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

    fetch('/test/api/orders.php?action=place_order', {
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

        window.location.href = '/test/views/orders.php';
    })
    .catch(err => {
        alert("發生錯誤：" + err);
    });
});

loadCheckoutCart();
loadCheckoutAddresses();
</script>
