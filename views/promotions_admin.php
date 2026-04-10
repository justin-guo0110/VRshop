<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: ./login.php');
    exit;
}
?>
<?php require_once __DIR__ . '/layout_header.php'; ?>

<style>
.promotions-admin-page {
    max-width: 900px;
    margin: 20px auto;
}

.promo-section {
    margin-bottom: 30px;
}

.promo-section .card {
    padding: 20px;
}

.promo-form-group {
    margin-bottom: 15px;
}

.promo-form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: 500;
    color: #333;
}

.promo-form-group input,
.promo-form-group textarea {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
    box-sizing: border-box;
}

.promo-form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
}

.shipping-config {
    background: #f9f9f9;
    padding: 15px;
    border-radius: 4px;
    margin-bottom: 15px;
}

.shipping-config h4 {
    margin-top: 15px;
    margin-bottom: 10px;
    font-size: 14px;
    color: #666;
}

.bundle-rule {
    background: #f9f9f9;
    padding: 15px;
    border-radius: 4px;
    margin-bottom: 15px;
}

.bundle-rule h4 {
    margin-bottom: 10px;
    font-size: 14px;
    color: #666;
}

.promo-form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
}

.btn-group {
    display: flex;
    gap: 10px;
    margin-top: 20px;
}

.btn {
    padding: 10px 20px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 14px;
    font-weight: 500;
}

.btn-primary {
    background: #007bff;
    color: white;
}

.btn-primary:hover {
    background: #0056b3;
}

.btn-secondary {
    background: #6c757d;
    color: white;
}

.btn-secondary:hover {
    background: #545b62;
}

.alert {
    padding: 12px 16px;
    border-radius: 4px;
    margin-bottom: 15px;
    display: none;
}

.alert.success {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.alert.error {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

.alert.show {
    display: block;
}

.config-info {
    background: #e7f3ff;
    padding: 10px 12px;
    border-radius: 4px;
    margin-top: 8px;
    font-size: 13px;
    color: #004085;
}
</style>

<div class="promotions-admin-page">
    <h1>促銷規則管理</h1>
    
    <div id="alertBox" class="alert"></div>

    <!-- 運費設定 -->
    <div class="promo-section">
        <div class="card">
            <h2>運費設定</h2>
            <p style="color: #666; font-size: 14px; margin-bottom: 15px;">調整配送費用和免運門檻，新設定將立即生效</p>
            
            <div class="shipping-config">
                <h4>宅配</h4>
                <div class="promo-form-row">
                    <div class="promo-form-group">
                        <label>基本運費 ($)</label>
                        <input type="number" id="homeFee" min="0" step="1" placeholder="100">
                        <div class="config-info">目前費用將從配置顯示</div>
                    </div>
                    <div class="promo-form-group">
                        <label>免運門檻 ($)</label>
                        <input type="number" id="homeThreshold" min="0" step="1" placeholder="499">
                        <div class="config-info">訂單金額超過此數即免運</div>
                    </div>
                </div>
            </div>

            <div class="shipping-config">
                <h4>超商取貨</h4>
                <div class="promo-form-row">
                    <div class="promo-form-group">
                        <label>基本運費 ($)</label>
                        <input type="number" id="convenienceFee" min="0" step="1" placeholder="60">
                    </div>
                    <div class="promo-form-group">
                        <label>免運門檻 ($)</label>
                        <input type="number" id="convenienceThreshold" min="0" step="1" placeholder="299">
                    </div>
                </div>
            </div>

            <button class="btn btn-primary" id="updateShippingBtn">儲存運費設定</button>
        </div>
    </div>

    <!-- 組合優惠設定 -->
    <div class="promo-section">
        <div class="card">
            <h2>組合優惠設定</h2>
            <p style="color: #666; font-size: 14px; margin-bottom: 15px;">設定商品組合優惠規則，新設定將立即生效</p>
            
            <div class="bundle-rule">
                <h4>飲料組合優惠</h4>
                <div class="promo-form-row">
                    <div class="promo-form-group">
                        <label>最少購買數量 (件)</label>
                        <input type="number" id="beverageQty" min="1" step="1" placeholder="2">
                        <div class="config-info">達到此數量即可享受優惠</div>
                    </div>
                    <div class="promo-form-group">
                        <label>折扣百分比 (%)</label>
                        <input type="number" id="beveragePercent" min="0" max="100" step="0.5" placeholder="12">
                        <div class="config-info">例：12表示打88折</div>
                    </div>
                </div>
            </div>

            <div class="bundle-rule">
                <h4>零食組合優惠</h4>
                <div class="promo-form-row">
                    <div class="promo-form-group">
                        <label>最少購買數量 (件)</label>
                        <input type="number" id="snackQty" min="1" step="1" placeholder="3">
                        <div class="config-info">達到此數量即可享受優惠</div>
                    </div>
                    <div class="promo-form-group">
                        <label>每組折扣金額 ($)</label>
                        <input type="number" id="snackFixed" min="0" step="1" placeholder="20">
                        <div class="config-info">例：購買3件或6件時分別折扣一次</div>
                    </div>
                </div>
            </div>

            <button class="btn btn-primary" id="updateBundleBtn">儲存組合優惠設定</button>
        </div>
    </div>
</div>

<script>
const app = window.app || {};

async function loadConfig() {
    try {
        const response = await fetch('../api/promotion_admin.php?action=get_config');
        const data = await response.json();
        
        if (!data.success) {
            showAlert('error', data.error || '無法載入配置');
            return;
        }

        // 填充運費配置
        const shipping = data.shipping || {};
        document.getElementById('homeFee').value = shipping.home_fee?.value || '100';
        document.getElementById('homeThreshold').value = shipping.home_threshold?.value || '499';
        document.getElementById('convenienceFee').value = shipping.convenience_fee?.value || '60';
        document.getElementById('convenienceThreshold').value = shipping.convenience_threshold?.value || '299';

        // 填充組合優惠配置
        const bundle = data.bundle || {};
        document.getElementById('beverageQty').value = bundle.beverage_discount_qty?.value || '2';
        document.getElementById('beveragePercent').value = bundle.beverage_discount_percent?.value || '12';
        document.getElementById('snackQty').value = bundle.snack_discount_qty?.value || '3';
        document.getElementById('snackFixed').value = bundle.snack_discount_fixed?.value || '20';
    } catch (err) {
        console.error('Failed to load config:', err);
        showAlert('error', '載入配置失敗');
    }
}

async function updateShippingConfig() {
    const homeFee = document.getElementById('homeFee').value;
    const homeThreshold = document.getElementById('homeThreshold').value;
    const convenienceFee = document.getElementById('convenienceFee').value;
    const convenienceThreshold = document.getElementById('convenienceThreshold').value;

    if (!homeFee || !homeThreshold || !convenienceFee || !convenienceThreshold) {
        showAlert('error', '請填入所有運費設定值');
        return;
    }

    try {
        const formData = new FormData();
        formData.append('home_fee', homeFee);
        formData.append('home_threshold', homeThreshold);
        formData.append('convenience_fee', convenienceFee);
        formData.append('convenience_threshold', convenienceThreshold);

        const response = await fetch('../api/promotion_admin.php?action=update_shipping', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();
        if (data.success) {
            showAlert('success', data.message || '運費設定已更新');
        } else {
            showAlert('error', data.error || '更新失敗');
        }
    } catch (err) {
        console.error('Failed to update shipping config:', err);
        showAlert('error', '更新失敗：' + err.message);
    }
}

async function updateBundleConfig() {
    const beverageQty = document.getElementById('beverageQty').value;
    const beveragePercent = document.getElementById('beveragePercent').value;
    const snackQty = document.getElementById('snackQty').value;
    const snackFixed = document.getElementById('snackFixed').value;

    if (!beverageQty || !beveragePercent || !snackQty || !snackFixed) {
        showAlert('error', '請填入所有組合優惠設定值');
        return;
    }

    try {
        const formData = new FormData();
        formData.append('beverage_qty', beverageQty);
        formData.append('beverage_percent', beveragePercent);
        formData.append('snack_qty', snackQty);
        formData.append('snack_fixed', snackFixed);

        const response = await fetch('../api/promotion_admin.php?action=update_bundle', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();
        if (data.success) {
            showAlert('success', data.message || '組合優惠設定已更新');
        } else {
            showAlert('error', data.error || '更新失敗');
        }
    } catch (err) {
        console.error('Failed to update bundle config:', err);
        showAlert('error', '更新失敗：' + err.message);
    }
}

function showAlert(type, message) {
    const alertBox = document.getElementById('alertBox');
    alertBox.className = `alert ${type} show`;
    alertBox.textContent = message;
    
    if (type === 'success') {
        setTimeout(() => {
            alertBox.classList.remove('show');
        }, 3000);
    }
}

// 事件監聽
document.getElementById('updateShippingBtn').addEventListener('click', updateShippingConfig);
document.getElementById('updateBundleBtn').addEventListener('click', updateBundleConfig);

// 載入頁面時獲取配置
document.addEventListener('DOMContentLoaded', loadConfig);
</script>

<?php require_once __DIR__ . '/layout_footer.php'; ?>
