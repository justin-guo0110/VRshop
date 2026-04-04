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

    <div id="orderSuccessPromotion" class="order-success-promotion" style="display:none;">
        <h3>本次訂單享受的優惠</h3>
        <div id="promotionDetailsList" class="promotion-details-list"></div>
    </div>

    <div id="orderSuccessDetail" class="order-success-detail">
        <div class="order-detail-card">明細載入中...</div>
    </div>

    <div class="order-success-actions">
        <a class="btn btn-secondary" href="./products.php">繼續購物</a>
        <a class="btn" id="viewOrdersBtn" href="./orders.php">前往我的訂單</a>
    </div>
</section>

<script>
async function loadOrderSuccess() {
    const params = new URLSearchParams(window.location.search);
    const orderId = params.get('order_id');
    
    if (!orderId) {
        document.getElementById('orderSuccessOrderId').textContent = '未知';
        return;
    }

    document.getElementById('orderSuccessOrderId').textContent = '#' + orderId;

    // 從 sessionStorage 中取出優惠詳情
    const promoData = JSON.parse(sessionStorage.getItem('lastOrderPromotion') || '{}');
    
    // 顯示優惠詳情
    if (promoData.promotion_details && promoData.promotion_details.length > 0) {
        const promoSection = document.getElementById('orderSuccessPromotion');
        const promoList = document.getElementById('promotionDetailsList');
        
        let html = '';
        let totalPromoDiscount = 0;
        
        for (const detail of promoData.promotion_details) {
            const label = detail.label || detail.code;
            const discount = parseFloat(detail.discount || 0);
            totalPromoDiscount += discount;
            
            html += `
                <div class="promotion-detail-item">
                    <span class="promo-label">${label}</span>
                    <span class="promo-discount">-$ ${discount.toFixed(0)}</span>
                </div>
            `;
        }
        
        if (promoData.coupon_discount && promoData.coupon_discount > 0) {
            html += `
                <div class="promotion-detail-item">
                    <span class="promo-label">優惠券折扣</span>
                    <span class="promo-discount">-$ ${parseFloat(promoData.coupon_discount).toFixed(0)}</span>
                </div>
            `;
        }
        
        if (promoData.shipping_fee === 0 && promoData.subtotal > 0) {
            html += `
                <div class="promotion-detail-item">
                    <span class="promo-label">滿額免運</span>
                    <span class="promo-discount">免運費</span>
                </div>
            `;
        }
        
        if (html) {
            promoList.innerHTML = html;
            promoSection.style.display = 'block';
        }
    }

    // 清除 sessionStorage 中的優惠詳情
    sessionStorage.removeItem('lastOrderPromotion');
}

document.addEventListener('DOMContentLoaded', loadOrderSuccess);
</script>

<?php require_once __DIR__ . '/layout_footer.php'; ?>
