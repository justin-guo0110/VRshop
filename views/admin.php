<?php require_once __DIR__ . '/layout_header.php'; ?>
<?php if (!$currentUser || ($currentUser['role'] ?? '') !== 'admin'): ?>
    <div class="card">
        <p>您沒有權限訪問此頁面。</p>
    </div>
<?php else: ?>

<div class="admin-container">
    <!-- 頁面內容 -->
    <section class="admin-content">
            <!-- 儀表板頁面 -->
            <div id="dashboardPage" class="admin-page active">
                <div class="admin-stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon">📦</div>
                        <div class="stat-info">
                            <p class="stat-label">總訂單</p>
                            <p id="statTotalOrders" class="stat-number">0</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">⏳</div>
                        <div class="stat-info">
                            <p class="stat-label">待處理</p>
                            <p id="statPending" class="stat-number">0</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">💰</div>
                        <div class="stat-info">
                            <p class="stat-label">今日收入</p>
                            <p id="statTotalRevenue" class="stat-number">$0.00</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">⚠️</div>
                        <div class="stat-info">
                            <p class="stat-label">低庫存</p>
                            <p id="statLowStock" class="stat-number">0</p>
                        </div>
                    </div>
                </div>

                <div class="admin-card">
                    <h3>📉 每日銷售額（近14天）</h3>
                    <div style="position:relative;height:260px;">
                        <canvas id="salesTrendChart" height="260" style="width:100%;height:260px;"></canvas>
                    </div>
                    <div id="salesTrendNote" style="margin-top:8px;color:#64748b;font-size:13px;">載入中...</div>
                </div>

                <div class="admin-grid">
                    <div class="admin-card">
                        <h3>📈 最近訂單</h3>
                        <table class="admin-table" id="recentOrdersTable">
                            <thead>
                                <tr>
                                    <th>訂單ID</th><th>顧客</th><th>金額</th><th>狀態</th><th>時間</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                    <div class="admin-card">
                        <h3>🔥 熱銷商品</h3>
                        <table class="admin-table" id="topProductsTable">
                            <thead>
                                <tr>
                                    <th>商品名</th><th>庫存</th><th>價格</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- 訂單管理頁面 -->
            <div id="ordersPage" class="admin-page">
                <div class="admin-card">
                    <h3>📋 訂單列表</h3>
                    <div class="admin-toolbar">
                        <input type="text" placeholder="搜尋訂單編號或顧客" class="admin-input">
                    </div>
                    <div class="order-status-filters">
                        <button type="button" class="order-filter-btn active" data-status="all">全部</button>
                        <button type="button" class="order-filter-btn" data-status="accepted">已接單</button>
                        <button type="button" class="order-filter-btn" data-status="preparing">準備中</button>
                        <button type="button" class="order-filter-btn" data-status="shipping">運送中</button>
                        <button type="button" class="order-filter-btn" data-status="done">已完成</button>
                    </div>
                    <table class="admin-table" id="ordersTable">
                        <thead>
                            <tr>
                                <th>ID</th><th>顧客</th><th>狀態</th><th>金額</th><th>日期</th><th>操作</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>

            <!-- 退單審核頁面 -->
            <div id="refundsPage" class="admin-page">
                <div class="admin-card">
                    <h3>↩️ 退單審核</h3>
                    <div class="admin-toolbar" style="gap:10px;flex-wrap:wrap;">
                        <input type="text" id="refundSearchInput" placeholder="搜尋申請編號 / 訂單編號 / 顧客" class="admin-input">
                    </div>
                    <div class="order-status-filters" id="refundStatusFilters">
                        <button type="button" class="refund-filter-btn active" data-status="pending">待審核</button>
                        <button type="button" class="refund-filter-btn" data-status="approved">已同意</button>
                        <button type="button" class="refund-filter-btn" data-status="rejected">已拒絕</button>
                        <button type="button" class="refund-filter-btn" data-status="all">全部</button>
                    </div>
                    <table class="admin-table" id="refundsTable">
                        <thead>
                            <tr>
                                <th>申請ID</th><th>訂單ID</th><th>顧客</th><th>原因/補充</th><th>申請時間</th><th>狀態</th><th>操作</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>

            <!-- 商品管理頁面 -->
            <div id="productsPage" class="admin-page">
                <div class="admin-card">
                    <div class="admin-toolbar">
                        <h3>📦 商品列表</h3>
                        <button class="btn btn-primary" id="newProductBtn">+ 新增商品</button>
                    </div>
                    
                    <!-- 新增商品表單 -->
                    <div id="newProductForm" style="display:none; background:#f9f9f9; padding:20px; margin-bottom:20px; border-radius:8px;">
                        <h4>新增商品</h4>
                        <div style="display:grid; grid-template-columns:1fr 1fr; gap:15px;">
                            <div>
                                <label>商品名稱 *</label>
                                <input type="text" id="newProductName" placeholder="商品名稱" required>
                            </div>
                            <div>
                                <label>分類 *</label>
                                <input type="text" id="newProductCategory" placeholder="分類" required>
                            </div>
                            <div>
                                <label>販賣價格 ($) *</label>
                                <input type="number" id="newProductPrice" placeholder="0.00" step="0.01" min="0" required>
                            </div>
                            <div>
                                <label>庫存數量 *</label>
                                <input type="number" id="newProductStock" placeholder="0" min="0" value="0" required>
                            </div>
                            <div style="grid-column:1/-1;">
                                <label>描述</label>
                                <textarea id="newProductDescription" placeholder="商品描述" rows="3"></textarea>
                            </div>
                            <div style="grid-column:1/-1;">
                                <label>圖片 URL</label>
                                <input type="url" id="newProductImageUrl" placeholder="https://...">
                            </div>
                            <div style="grid-column:1/-1; display:flex; gap:10px;">
                                <button class="btn btn-success" id="createProductBtn">確認新增</button>
                                <button class="btn btn-secondary" id="cancelProductBtn">取消</button>
                            </div>
                        </div>
                    </div>
                    
                    <table class="admin-table" id="productsTable">
                        <thead>
                            <tr>
                                <th>ID</th><th>名稱/圖片</th><th>分類</th><th>價格</th><th>庫存</th><th>狀態</th><th>圖片URL / 操作</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>

            <!-- 庫存管理頁面 -->
            <div id="inventoryPage" class="admin-page">
                <div class="admin-grid">
                    <div class="admin-card">
                        <h3>📥 新增進貨</h3>
                        <form id="receivingForm">
                            <div class="form-group">
                                <label>商品</label>
                                <select name="product_id" id="receivingProduct"></select>
                            </div>
                            <div class="form-group">
                                <label>數量</label>
                                <input type="number" id="receivingQty" name="qty" min="1" value="1">
                            </div>
                            <div class="form-group">
                                <label>供應商</label>
                                <input type="text" id="receivingSupplier" name="supplier_name" placeholder="例如：統一供應鏈">
                            </div>
                            <div class="form-group">
                                <label>單價</label>
                                <input type="number" id="receivingUnitCost" step="0.01" min="0" name="unit_cost" placeholder="例如：18.5">
                            </div>
                            <div class="form-group">
                                <label>進貨時間（可選）</label>
                                <input type="datetime-local" id="receivingAt" name="received_at">
                            </div>
                            <div class="form-group">
                                <label>備註</label>
                                <input type="text" id="receivingNote" name="note" placeholder="例如：首批補貨">
                            </div>
                            <button type="submit" class="btn btn-primary">儲存</button>
                            <div id="receivingMessage" class="message" style="margin-top:10px;"></div>
                        </form>
                    </div>

                    <div class="admin-card">
                        <h3>📚 近期進貨</h3>
                        <table class="admin-table" id="receivingTable">
                            <thead>
                                <tr>
                                    <th>ID</th><th>供應商</th><th>項目</th><th>總成本</th><th>日期</th><th>備註</th><th>操作</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                        <div id="receivingDetail" style="margin-top:14px;"></div>
                    </div>
                </div>

                <div class="admin-grid" style="margin-top:16px;">
                    <div class="admin-card">
                        <h3>📦 庫存商品管理</h3>
                        <table class="admin-table" id="inventoryProductsTable">
                            <thead>
                                <tr>
                                    <th>ID</th><th>商品</th><th>分類</th><th>現有庫存</th><th>價格</th><th>操作</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>

                    <div class="admin-card">
                        <h3>🧾 庫存異動紀錄</h3>
                        <div class="admin-toolbar" style="gap:10px;">
                            <select id="movementFilterProduct" class="admin-input" style="max-width:320px;">
                                <option value="">全部商品</option>
                            </select>
                            <button id="movementFilterBtn" class="btn btn-secondary" type="button">篩選</button>
                            <button id="movementClearBtn" class="btn btn-secondary" type="button">清除</button>
                        </div>
                        <table class="admin-table" id="movementsTable">
                            <thead>
                                <tr>
                                    <th>ID</th><th>商品</th><th>類型</th><th>增減</th><th>來源</th><th>備註</th><th>時間</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- 客服系統頁面 -->
            <div id="chatPage" class="admin-page">
                <div class="admin-card" style="height: 600px; display: flex;">
                    <div class="admin-chat-list">
                        <input type="text" placeholder="搜尋客戶..." class="admin-input">
                        <div id="adminChatList"></div>
                    </div>
                    <div class="admin-chat-box">
                        <div class="admin-chat-header" id="chatTitle">選擇一個聊天對象</div>
                        <div class="admin-chat-messages" id="adminChatMessages"></div>
                        <div class="admin-chat-input">
                            <input type="text" id="adminChatInput" placeholder="回覆訊息...">
                            <button id="adminChatSendBtn">送出</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 客戶管理 -->
            <div id="customersPage" class="admin-page" style="display:none;">
                <div class="admin-card">
                    <h3>👥 客戶列表</h3>
                    <table class="admin-table" id="customersTable">
                        <thead><tr><th>ID</th><th>名稱</th><th>郵箱</th><th>標籤</th><th>操作</th></tr></thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>

            <!-- 熱門商品管理 -->
            <div id="featured_productsPage" class="admin-page" style="display:none;">
                <div class="admin-card">
                    <h3>⭐ 熱門商品管理</h3>
                    <p style="margin-top:-8px;color:#64748b;">設定首頁熱門商品卡片的商品與標籤，最多 6 筆，儲存後前台立即生效。</p>

                    <table class="admin-table" id="featuredProductsAdminTable">
                        <thead>
                            <tr>
                                <th style="width:84px;">排序</th>
                                <th>商品</th>
                                <th style="width:260px;">推薦標籤</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>

                    <div style="display:flex;gap:10px;align-items:center;margin-top:12px;">
                        <button type="button" class="btn btn-primary" id="saveFeaturedProductsBtn">儲存熱門商品</button>
                        <span id="featuredProductsMsg" style="font-size:13px;color:#64748b;"></span>
                    </div>
                </div>
            </div>

            <!-- 側邊廣告管理 -->
            <div id="sidebar_adsPage" class="admin-page" style="display:none;">
                <div class="admin-card">
                    <h3>🖼️ 側邊廣告管理</h3>
                    <p style="margin-top:-8px;color:#64748b;">首頁左側廣告僅顯示圖片，直接上傳素材即可投放。未上傳時前台顯示「歡迎諮詢投廣」。</p>

                    <table class="admin-table" id="sidebarAdsAdminTable">
                        <thead>
                            <tr>
                                <th style="width:70px;">版位</th>
                                <th style="width:190px;">預覽</th>
                                <th>狀態</th>
                                <th style="width:220px;">上傳素材</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>

                    <div style="display:flex;gap:10px;align-items:center;margin-top:12px;">
                        <button type="button" class="btn btn-secondary" id="saveSidebarAdsBtn">手動儲存（可選）</button>
                        <span id="sidebarAdsMsg" style="font-size:13px;color:#64748b;"></span>
                    </div>
                </div>
            </div>

            <!-- 促銷管理 -->
            <div id="promotionsPage" class="admin-page" style="display:none;">
                <div class="admin-card">
                    <div class="admin-toolbar"><h3>🎁 促銷活動</h3><button class="btn btn-primary" id="newPromotionBtn">+ 新建促銷</button></div>
                    <div id="promotionFormWrap" style="display:none;background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:14px;margin-bottom:14px;">
                        <input type="hidden" id="promoFormId" value="">
                        <div class="admin-grid" style="grid-template-columns:1fr 1fr;gap:12px;">
                            <div>
                                <label style="font-weight:700;display:block;margin-bottom:6px;">活動標題</label>
                                <input type="text" id="promoFormTitle" class="admin-input" placeholder="例如：春季全館折扣">
                            </div>
                            <div>
                                <label style="font-weight:700;display:block;margin-bottom:6px;">促銷類型</label>
                                <select id="promoFormType" class="admin-input">
                                    <option value="global_discount">全館折扣</option>
                                    <option value="threshold_discount">滿額折扣</option>
                                    <option value="add_on_purchase">加價購</option>
                                    <option value="bundle">組合優惠</option>
                                    <option value="free_shipping">免運</option>
                                    <option value="coupon">優惠碼</option>
                                </select>
                            </div>
                            <div style="grid-column:1/-1;">
                                <label style="font-weight:700;display:block;margin-bottom:6px;">活動描述</label>
                                <textarea id="promoFormDesc" class="admin-input" rows="2" placeholder="活動說明"></textarea>
                            </div>
                            <div>
                                <label style="font-weight:700;display:block;margin-bottom:6px;">折扣類型</label>
                                <select id="promoFormDiscountType" class="admin-input">
                                    <option value="percent">百分比</option>
                                    <option value="fixed">固定金額</option>
                                </select>
                            </div>
                            <div>
                                <label style="font-weight:700;display:block;margin-bottom:6px;">折扣值</label>
                                <input type="number" id="promoFormDiscountValue" min="0" step="0.01" class="admin-input" placeholder="10">
                            </div>
                            <div>
                                <label style="font-weight:700;display:block;margin-bottom:6px;">最低消費</label>
                                <input type="number" id="promoFormMinAmount" min="0" step="0.01" class="admin-input" placeholder="0">
                            </div>
                            <div>
                                <label style="font-weight:700;display:block;margin-bottom:6px;">最大折扣（可空）</label>
                                <input type="number" id="promoFormMaxDiscount" min="0" step="0.01" class="admin-input" placeholder="例如：200">
                            </div>
                            <div>
                                <label style="font-weight:700;display:block;margin-bottom:6px;">指定商品ID（可空）</label>
                                <input type="number" id="promoFormProductId" min="1" step="1" class="admin-input" placeholder="例如：12">
                            </div>
                            <div>
                                <label style="font-weight:700;display:block;margin-bottom:6px;">開始時間</label>
                                <input type="datetime-local" id="promoFormStartDate" class="admin-input">
                            </div>
                            <div>
                                <label style="font-weight:700;display:block;margin-bottom:6px;">結束時間</label>
                                <input type="datetime-local" id="promoFormEndDate" class="admin-input">
                            </div>
                        </div>
                        <div style="display:flex;gap:10px;margin-top:12px;align-items:center;">
                            <button type="button" class="btn btn-primary" id="savePromotionBtn">儲存促銷</button>
                            <button type="button" class="btn btn-secondary" id="cancelPromotionBtn">取消</button>
                            <span id="promotionFormMsg" style="font-size:13px;color:#64748b;"></span>
                        </div>
                    </div>
                    <table class="admin-table" id="promotionsTable">
                        <thead><tr><th>名稱</th><th>類型</th><th>折扣</th><th>開始日期</th><th>狀態</th><th>操作</th></tr></thead>
                        <tbody></tbody>
                    </table>
                </div>

                <div class="admin-card">
                    <h3>⚡ 促銷規則設定</h3>
                    <p style="margin-top:-8px;color:#64748b;">同頁調整免運門檻、運費與組合優惠，儲存後立即生效。</p>
                    <div id="promotionRuleMsg" class="message" style="display:none;margin:8px 0 14px;"></div>

                    <h4 style="margin:8px 0 10px;">運費設定</h4>
                    <div class="admin-grid" style="grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px;">
                        <div>
                            <label style="font-weight:700;display:block;margin-bottom:6px;">宅配運費</label>
                            <input type="number" id="promoRuleHomeFee" min="0" step="1" class="admin-input" placeholder="100">
                        </div>
                        <div>
                            <label style="font-weight:700;display:block;margin-bottom:6px;">宅配免運門檻</label>
                            <input type="number" id="promoRuleHomeThreshold" min="0" step="1" class="admin-input" placeholder="499">
                        </div>
                        <div>
                            <label style="font-weight:700;display:block;margin-bottom:6px;">超商運費</label>
                            <input type="number" id="promoRuleConvenienceFee" min="0" step="1" class="admin-input" placeholder="60">
                        </div>
                        <div>
                            <label style="font-weight:700;display:block;margin-bottom:6px;">超商免運門檻</label>
                            <input type="number" id="promoRuleConvenienceThreshold" min="0" step="1" class="admin-input" placeholder="299">
                        </div>
                    </div>
                    <div style="margin-bottom:18px;">
                        <button type="button" class="btn btn-primary" id="promoRuleSaveShippingBtn">儲存運費設定</button>
                    </div>

                    <h4 style="margin:8px 0 10px;">組合優惠設定</h4>
                    <div class="admin-grid" style="grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px;">
                        <div>
                            <label style="font-weight:700;display:block;margin-bottom:6px;">飲料最少件數</label>
                            <input type="number" id="promoRuleBeverageQty" min="1" step="1" class="admin-input" placeholder="2">
                        </div>
                        <div>
                            <label style="font-weight:700;display:block;margin-bottom:6px;">飲料折扣百分比(%)</label>
                            <input type="number" id="promoRuleBeveragePercent" min="0" max="100" step="0.5" class="admin-input" placeholder="12">
                        </div>
                        <div>
                            <label style="font-weight:700;display:block;margin-bottom:6px;">零食最少件數</label>
                            <input type="number" id="promoRuleSnackQty" min="1" step="1" class="admin-input" placeholder="3">
                        </div>
                        <div>
                            <label style="font-weight:700;display:block;margin-bottom:6px;">零食每組折扣金額</label>
                            <input type="number" id="promoRuleSnackFixed" min="0" step="1" class="admin-input" placeholder="20">
                        </div>
                    </div>
                    <button type="button" class="btn btn-primary" id="promoRuleSaveBundleBtn">儲存組合優惠設定</button>
                </div>

                <div class="admin-card">
                    <h3>🎡 轉盤獎項設定（8格）</h3>
                    <p style="margin-top:-8px;color:#64748b;">管理員可修改獎項名稱、類型、數值與最低消費。儲存後前台轉盤立即套用。</p>
                    <table class="admin-table" id="wheelPrizesTable">
                        <thead>
                            <tr>
                                <th style="width:70px;">編號</th>
                                <th>顯示名稱</th>
                                <th style="width:130px;">類型</th>
                                <th style="width:120px;">數值</th>
                                <th style="width:140px;">最低消費</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                    <div style="margin-top:12px;display:flex;gap:10px;align-items:center;">
                        <button class="btn btn-primary" id="saveWheelPrizesBtn">儲存轉盤設定</button>
                        <span id="wheelPrizesMsg" style="font-size:14px;"></span>
                    </div>
                </div>
            </div>
        </section>
    </div>

<script>
// API 調用工具
const opsApi = {
    get: (url) => fetch(url, { credentials: 'include' }).then(r => r.json()),
    post: (url, data) => {
        const options = {
            method: 'POST',
            credentials: 'include',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
        };
        options.body = new URLSearchParams(data);
        return fetch(url, options).then(r => r.json());
    }
};

const orderStatusLabels = {
    pending: '已接單',      // 舊數據相容
    accepted: '已接單',
    preparing: '準備中',
    shipping: '運送中',
    done: '已完成',
    cancelled: '已取消'
};

let currentOrderStatus = 'all';
let currentOrderKeyword = '';
let currentRefundStatus = 'pending';
let currentRefundKeyword = '';
let sidebarAdsState = [];
const SIDEBAR_AD_PLACEHOLDER = 'https://via.placeholder.com/240x120?text=%E6%AD%A1%E8%BF%8E%E8%AB%AE%E8%A9%A2%E6%8A%95%E5%BB%A3';

const refundStatusLabels = {
    pending: '待審核',
    approved: '已同意',
    rejected: '已拒絕'
};

function escapeHtml(value) {
    return String(value ?? '').replace(/[&<>"']/g, function (char) {
        return ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#39;'
        })[char];
    });
}

// 頁面導航邏輯
document.addEventListener('DOMContentLoaded', function() {
    // 根據 URL 參數顯示對應的頁面
    const params = new URLSearchParams(window.location.search);
    const page = params.get('page') || 'dashboard';
    showPage(page);
    
    // 登出按鈕
    const logoutBtn = document.getElementById('logoutBtn');
    if (logoutBtn) {
        logoutBtn.addEventListener('click', () => {
            if (confirm('確定要登出嗎？')) {
                window.location.href = '../api/auth.php?logout=1';
            }
        });
    }

    const orderSearchInput = document.querySelector('#ordersPage .admin-toolbar .admin-input');
    if (orderSearchInput) {
        orderSearchInput.addEventListener('input', function () {
            currentOrderKeyword = orderSearchInput.value.trim();
            loadOrders();
        });
    }

    document.querySelectorAll('#ordersPage .order-filter-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            currentOrderStatus = btn.getAttribute('data-status') || 'all';
            document.querySelectorAll('#ordersPage .order-filter-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            loadOrders();
        });
    });

    const refundSearchInput = document.getElementById('refundSearchInput');
    if (refundSearchInput) {
        refundSearchInput.addEventListener('input', function () {
            currentRefundKeyword = refundSearchInput.value.trim();
            loadRefundRequests();
        });
    }

    document.querySelectorAll('#refundsPage .refund-filter-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            currentRefundStatus = btn.getAttribute('data-status') || 'pending';
            document.querySelectorAll('#refundsPage .refund-filter-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            loadRefundRequests();
        });
    });
});

// 顯示指定頁面
function showPage(page) {
    // 隱藏所有頁面
    document.querySelectorAll('.admin-page').forEach(p => p.style.display = 'none');
    
    // 顯示對應頁面
    const pageEl = document.getElementById(page + 'Page');
    if (pageEl) {
        pageEl.style.display = 'block';
        
        // 加載相應數據
        if (page === 'dashboard') loadDashboard();
        else if (page === 'products') loadProducts();
        else if (page === 'orders') loadOrders();
        else if (page === 'refunds') loadRefundRequests();
        else if (page === 'inventory') loadInventory();
        else if (page === 'customers') loadCustomers();
        else if (page === 'featured_products') loadFeaturedProductsAdmin();
        else if (page === 'sidebar_ads') loadSidebarAdsAdmin();
        else if (page === 'promotions') loadPromotions();
        else if (page === 'chat') loadChat();
    }
}

// 加載銷售看板
async function loadDashboard() {
    try {
        const data = await opsApi.get('../api/admin.php?action=dashboard_stats');
        const stats = data.stats || {};

        const setText = (id, value) => {
            const el = document.getElementById(id);
            if (el) el.textContent = value;
        };

        setText('statTotalOrders', Number(stats.total_orders || 0));
        setText('statPending', Number(stats.pending_count || 0));
        setText('statTotalRevenue', '$' + Number(stats.today_revenue || 0).toFixed(2));
        setText('statLowStock', Number(stats.low_stock_count || 0));

        renderRecentOrders(data.recent_orders || []);
        renderTopProducts(data.top_products || []);
        drawSalesTrendChart(data.daily_sales || []);
    } catch (error) {
        console.error('加載看板:', error);
        const note = document.getElementById('salesTrendNote');
        if (note) note.textContent = '載入失敗，請稍後再試';
    }
}

function renderRecentOrders(orders) {
    const tbody = document.querySelector('#recentOrdersTable tbody');
    if (!tbody) return;

    tbody.innerHTML = '';
    if (!orders.length) {
        tbody.innerHTML = '<tr><td colspan="5" style="text-align:center;color:#94a3b8;">目前沒有訂單</td></tr>';
        return;
    }

    orders.forEach(o => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>#${escapeHtml(o.order_id)}</td>
            <td>${escapeHtml(o.customer_name || '-')}</td>
            <td>$${Number(o.total_amount || 0).toFixed(2)}</td>
            <td>${escapeHtml(o.status || '-')}</td>
            <td>${escapeHtml(o.created_at || '-')}</td>
        `;
        tbody.appendChild(tr);
    });
}

function renderTopProducts(products) {
    const tbody = document.querySelector('#topProductsTable tbody');
    if (!tbody) return;

    tbody.innerHTML = '';
    if (!products.length) {
        tbody.innerHTML = '<tr><td colspan="3" style="text-align:center;color:#94a3b8;">目前沒有商品資料</td></tr>';
        return;
    }

    products.forEach(p => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>${escapeHtml(p.name || '-')}</td>
            <td>${Number(p.stock || 0)}</td>
            <td>$${Number(p.price || 0).toFixed(2)}</td>
        `;
        tbody.appendChild(tr);
    });
}

function drawSalesTrendChart(points) {
    const canvas = document.getElementById('salesTrendChart');
    const note = document.getElementById('salesTrendNote');
    if (!canvas) return;

    const ctx = canvas.getContext('2d');
    if (!ctx) return;

    const dpr = window.devicePixelRatio || 1;
    const cssWidth = canvas.clientWidth || canvas.parentElement?.clientWidth || 600;
    const cssHeight = canvas.clientHeight || 260;
    canvas.width = Math.floor(cssWidth * dpr);
    canvas.height = Math.floor(cssHeight * dpr);
    ctx.setTransform(dpr, 0, 0, dpr, 0, 0);

    ctx.clearRect(0, 0, cssWidth, cssHeight);

    if (!Array.isArray(points) || points.length === 0) {
        if (note) note.textContent = '暫無銷售資料';
        return;
    }

    const labels = points.map(p => String(p.date || '').slice(5));
    const values = points.map(p => Number(p.revenue || 0));
    const maxVal = Math.max(...values, 1);

    const pad = { left: 42, right: 16, top: 18, bottom: 34 };
    const chartW = cssWidth - pad.left - pad.right;
    const chartH = cssHeight - pad.top - pad.bottom;

    const x = i => pad.left + (i * chartW) / Math.max(values.length - 1, 1);
    const y = v => pad.top + chartH - (v / maxVal) * chartH;

    // 網格線
    ctx.strokeStyle = '#e2e8f0';
    ctx.lineWidth = 1;
    for (let i = 0; i <= 4; i++) {
        const yy = pad.top + (chartH / 4) * i;
        ctx.beginPath();
        ctx.moveTo(pad.left, yy);
        ctx.lineTo(cssWidth - pad.right, yy);
        ctx.stroke();

        const value = ((maxVal * (4 - i)) / 4).toFixed(0);
        ctx.fillStyle = '#64748b';
        ctx.font = '12px sans-serif';
        ctx.fillText(`$${value}`, 4, yy + 4);
    }

    // 折線
    ctx.strokeStyle = '#0f766e';
    ctx.lineWidth = 2.5;
    ctx.beginPath();
    values.forEach((v, i) => {
        const px = x(i);
        const py = y(v);
        if (i === 0) ctx.moveTo(px, py);
        else ctx.lineTo(px, py);
    });
    ctx.stroke();

    // 填色
    ctx.beginPath();
    values.forEach((v, i) => {
        const px = x(i);
        const py = y(v);
        if (i === 0) ctx.moveTo(px, py);
        else ctx.lineTo(px, py);
    });
    ctx.lineTo(x(values.length - 1), pad.top + chartH);
    ctx.lineTo(x(0), pad.top + chartH);
    ctx.closePath();
    ctx.fillStyle = 'rgba(20, 184, 166, 0.12)';
    ctx.fill();

    // 點
    ctx.fillStyle = '#0f766e';
    values.forEach((v, i) => {
        ctx.beginPath();
        ctx.arc(x(i), y(v), 3, 0, Math.PI * 2);
        ctx.fill();
    });

    // X 軸標籤（顯示 7 個點避免擁擠）
    ctx.fillStyle = '#64748b';
    ctx.font = '11px sans-serif';
    const step = Math.max(1, Math.floor(labels.length / 7));
    labels.forEach((lb, i) => {
        if (i % step !== 0 && i !== labels.length - 1) return;
        const px = x(i) - 14;
        ctx.fillText(lb, px, cssHeight - 10);
    });

    const total = values.reduce((sum, v) => sum + v, 0);
    if (note) {
        note.textContent = `近14天累計營業額：$${total.toFixed(2)}，最高單日：$${maxVal.toFixed(2)}`;
    }
}

// 加載商品列表
async function loadProducts() {
    try {
        const data = await opsApi.get('../api/shop.php?action=list_products');
        const products = data.products || [];
        const tbody = document.querySelector('#productsTable tbody');
        tbody.innerHTML = '';
        products.forEach(p => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>#${p.product_id}</td>
                <td>
                    <img src="${p.image_url || 'https://via.placeholder.com/40'}" style="width:40px; height:40px; border-radius:4px; margin-right:8px;" alt="${p.name}">
                    <input type="text" class="product-name" value="${p.name}" data-id="${p.product_id}" style="width:120px;">
                </td>
                <td><input type="text" class="product-category" value="${p.category}" style="width:80px;"></td>
                <td><input type="number" class="product-price" value="${p.price}" step="0.01" style="width:80px;"></td>
                <td><input type="number" class="product-stock" value="${p.stock}" style="width:60px;"></td>
                <td><input type="checkbox" class="product-active" ${p.is_active ? 'checked' : ''}></td>
                <td>
                    <input type="text" class="product-image-url" value="${p.image_url || ''}" placeholder="圖片URL" style="width:150px;">
                </td>
                <td>
                    <button class="btn btn-sm save-product" data-id="${p.product_id}" style="background:#0f766e; color:white; margin-bottom:4px; width:100%; cursor:pointer;">儲存</button>
                    <button class="btn btn-sm delete-product" data-id="${p.product_id}" style="background:#dc2626; color:white; cursor:pointer; width:100%;">刪除</button>
                </td>
            `;
            tbody.appendChild(tr);
        });
        
        // 添加保存和删除按钮的事件监听器
        document.querySelectorAll('.save-product').forEach(btn => {
            btn.addEventListener('click', async (e) => {
                e.preventDefault();
                const id = btn.getAttribute('data-id');
                const row = btn.closest('tr');
                const productData = {
                    action: 'update_product',
                    product_id: id,
                    name: row.querySelector('.product-name').value,
                    category: row.querySelector('.product-category').value,
                    price: row.querySelector('.product-price').value,
                    stock: row.querySelector('.product-stock').value,
                    image_url: row.querySelector('.product-image-url').value,
                    is_active: row.querySelector('.product-active').checked ? 1 : 0
                };
                
                try {
                    const result = await opsApi.post('../api/shop.php', productData);
                    if (result.success) {
                        alert('商品已保存！');
                        loadProducts(); // 刷新列表
                    } else {
                        alert('保存失敗：' + (result.error || '未知錯誤'));
                    }
                } catch (error) {
                    alert('保存失敗：' + error.message);
                }
            });
        });
        
        document.querySelectorAll('.delete-product').forEach(btn => {
            btn.addEventListener('click', async (e) => {
                e.preventDefault();
                if (!confirm('確定要刪除此商品嗎？')) return;
                
                const id = btn.getAttribute('data-id');
                try {
                    const result = await opsApi.post('../api/shop.php', {
                        action: 'delete_product',
                        product_id: id
                    });
                    if (result.success) {
                        alert('商品已刪除！');
                        loadProducts(); // 刷新列表
                    } else {
                        alert('刪除失敗：' + (result.error || '未知錯誤'));
                    }
                } catch (error) {
                    alert('刪除失敗：' + error.message);
                }
            });
        });
    } catch (error) {
        console.error('加載商品:', error);
    }
}

// 加載訂單列表
async function loadOrders() {
    try {
        const data = await opsApi.get('../api/admin.php?action=list_orders');
        const allOrders = data.orders || [];
        const tbody = document.querySelector('#ordersTable tbody');
        if (!tbody) return;

        let orders = allOrders;
        if (currentOrderStatus !== 'all') {
            orders = orders.filter(o => o.status === currentOrderStatus);
        }

        if (currentOrderKeyword !== '') {
            const keyword = currentOrderKeyword.toLowerCase();
            orders = orders.filter(o => {
                const id = String(o.order_id || '').toLowerCase();
                const name = String(o.name || '').toLowerCase();
                const email = String(o.email || '').toLowerCase();
                return id.includes(keyword) || name.includes(keyword) || email.includes(keyword);
            });
        }

        tbody.innerHTML = '';
        if (orders.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;color:#6b7280;">目前沒有符合條件的訂單</td></tr>';
            return;
        }

        orders.forEach(o => {
            const tr = document.createElement('tr');
            const status = o.status || 'accepted';
            tr.innerHTML = `
                <td>#${escapeHtml(o.order_id)}</td>
                <td>${escapeHtml(o.name || o.email || '-')}</td>
                <td><span class="badge">${escapeHtml(orderStatusLabels[status] || status)}</span></td>
                <td>$${Number(o.total_amount || 0).toFixed(2)}</td>
                <td>${escapeHtml(o.created_at || '-')}</td>
                <td>
                    <select class="order-status-select" data-order-id="${escapeHtml(o.order_id)}" style="margin-right:5px;">
                        ${['accepted', 'preparing', 'shipping', 'done', 'cancelled'].map(s => `<option value="${s}" ${s === status ? 'selected' : ''}>${orderStatusLabels[s]}</option>`).join('')}
                    </select>
                    <button class="btn btn-sm btn-danger delete-order-btn" data-order-id="${escapeHtml(o.order_id)}" style="padding:4px 8px;">刪除</button>
                </td>
            `;
            tbody.appendChild(tr);
        });

        tbody.querySelectorAll('.order-status-select').forEach(select => {
            select.addEventListener('change', async function () {
                const orderId = select.getAttribute('data-order-id');
                const status = select.value;
                try {
                    const result = await opsApi.post('../api/admin.php?action=update_order_status', {
                        order_id: orderId,
                        status
                    });
                    if (!result.success) {
                        alert('更新失敗：' + (result.error || '未知錯誤'));
                        loadOrders();
                        return;
                    }
                } catch (err) {
                    alert('更新狀態失敗');
                    loadOrders();
                }
            });
        });

        tbody.querySelectorAll('.delete-order-btn').forEach(btn => {
            btn.addEventListener('click', async function () {
                const orderId = btn.getAttribute('data-order-id');
                if (!confirm(`確定要刪除訂單 #${orderId} 嗎？`)) {
                    return;
                }
                try {
                    const result = await opsApi.post('../api/admin.php?action=delete_order', {
                        order_id: orderId
                    });
                    if (result.success) {
                        loadOrders();
                    } else {
                        alert('刪除失敗：' + (result.error || '未知錯誤'));
                    }
                } catch (err) {
                    alert('刪除訂單失敗');
                }
            });
        });
    } catch (error) {
        console.error('加載訂單:', error);
    }
}

async function loadRefundRequests() {
    try {
        const data = await opsApi.get('../api/admin.php?action=list_refund_requests');
        const allRequests = data.requests || [];
        const tbody = document.querySelector('#refundsTable tbody');
        if (!tbody) return;

        let requests = allRequests;
        if (currentRefundStatus !== 'all') {
            requests = requests.filter(r => r.status === currentRefundStatus);
        }

        if (currentRefundKeyword !== '') {
            const keyword = currentRefundKeyword.toLowerCase();
            requests = requests.filter(r => {
                const requestId = String(r.request_id || '').toLowerCase();
                const orderId = String(r.order_id || '').toLowerCase();
                const name = String(r.member_name || '').toLowerCase();
                const email = String(r.member_email || '').toLowerCase();
                return requestId.includes(keyword) || orderId.includes(keyword) || name.includes(keyword) || email.includes(keyword);
            });
        }

        tbody.innerHTML = '';
        if (requests.length === 0) {
            tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;color:#6b7280;">目前沒有符合條件的退單申請</td></tr>';
            return;
        }

        requests.forEach(r => {
            const tr = document.createElement('tr');
            const status = String(r.status || 'pending');
            const reason = escapeHtml(r.reason || '-');
            const note = escapeHtml(r.note || '');
            const reviewNote = escapeHtml(r.review_note || '');
            const canReview = status === 'pending';

            tr.innerHTML = `
                <td>#${escapeHtml(r.request_id)}</td>
                <td>#${escapeHtml(r.order_id)}</td>
                <td>
                    <div>${escapeHtml(r.member_name || '-')}</div>
                    <small style="color:#64748b;">${escapeHtml(r.member_email || '-')}</small>
                </td>
                <td>
                    <div style="font-weight:600;">${reason}</div>
                    <small style="color:#64748b;">${note || '-'}</small>
                </td>
                <td>${escapeHtml(r.created_at || '-')}</td>
                <td>
                    <span class="refund-admin-status refund-admin-${status}">${escapeHtml(refundStatusLabels[status] || status)}</span>
                    ${reviewNote ? `<div style="margin-top:6px;color:#64748b;"><small>審核備註：${reviewNote}</small></div>` : ''}
                </td>
                <td>
                    ${canReview ? `
                        <div class="refund-admin-actions">
                            <button type="button" class="btn btn-sm btn-primary review-refund-btn" data-request-id="${escapeHtml(r.request_id)}" data-decision="approved">同意</button>
                            <button type="button" class="btn btn-sm btn-danger review-refund-btn" data-request-id="${escapeHtml(r.request_id)}" data-decision="rejected">拒絕</button>
                        </div>
                    ` : '<span style="color:#64748b;">已完成審核</span>'}
                </td>
            `;
            tbody.appendChild(tr);
        });

        tbody.querySelectorAll('.review-refund-btn').forEach(btn => {
            btn.addEventListener('click', async function () {
                const requestId = btn.getAttribute('data-request-id');
                const decision = btn.getAttribute('data-decision');
                const actionText = decision === 'approved' ? '同意' : '拒絕';
                const reviewNote = prompt(`請輸入審核備註（選填）\n將執行：${actionText}退單申請 #${requestId}`) || '';
                if (!confirm(`確定要${actionText}退單申請 #${requestId} 嗎？`)) {
                    return;
                }

                try {
                    const result = await opsApi.post('../api/admin.php?action=review_refund_request', {
                        request_id: requestId,
                        decision,
                        review_note: reviewNote
                    });
                    if (result.success) {
                        loadRefundRequests();
                    } else {
                        alert('審核失敗：' + (result.error || '未知錯誤'));
                    }
                } catch (err) {
                    alert('審核操作失敗');
                }
            });
        });
    } catch (error) {
        console.error('加載退單申請:', error);
        const tbody = document.querySelector('#refundsTable tbody');
        if (tbody) {
            tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;color:#dc2626;">退單申請載入失敗</td></tr>';
        }
    }
}

// 加載客戶列表
async function loadCustomers() {
    try {
        const data = await opsApi.get('../api/admin.php?action=list_customers');
        const customers = data.customers || [];
        const tbody = document.querySelector('#customersTable tbody');
        if (!tbody) return;

        tbody.innerHTML = '';

        if (!customers.length) {
            tbody.innerHTML = '<tr><td colspan="5" style="text-align:center;color:#6b7280;">目前沒有客戶資料</td></tr>';
            return;
        }

        customers.forEach(c => {
            const labels = Array.isArray(c.labels) && c.labels.length
                ? c.labels.map(tag => `<span style="display:inline-block;padding:2px 8px;margin:2px;border-radius:999px;background:#ecfeff;color:#0f766e;font-size:12px;">${escapeHtml(tag)}</span>`).join('')
                : '<span style="color:#94a3b8;">—</span>';

            const spend = Number(c.total_spent || 0).toFixed(2);
            const orders = Number(c.order_count || 0);

            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>#${escapeHtml(c.member_id)}</td>
                <td>${escapeHtml(c.name || '未命名')}</td>
                <td>${escapeHtml(c.email || '-')}</td>
                <td>${labels}</td>
                <td style="white-space:nowrap;">
                    <span style="font-size:12px;color:#64748b;">訂單 ${orders} 筆 / 消費 $${spend}</span>
                </td>
            `;
            tbody.appendChild(tr);
        });
    } catch (error) {
        console.error('加載客戶:', error);
        const tbody = document.querySelector('#customersTable tbody');
        if (tbody) {
            tbody.innerHTML = '<tr><td colspan="5" style="text-align:center;color:#dc2626;">客戶資料載入失敗</td></tr>';
        }
    }
}

async function loadFeaturedProductsAdmin() {
    const tbody = document.querySelector('#featuredProductsAdminTable tbody');
    const msg = document.getElementById('featuredProductsMsg');
    if (!tbody) return;

    if (msg) {
        msg.textContent = '';
        msg.style.color = '#64748b';
    }

    try {
        const data = await opsApi.get('../api/admin.php?action=get_featured_products');
        const configRows = Array.isArray(data.featured_products) ? data.featured_products : [];
        const products = Array.isArray(data.products) ? data.products.filter(p => Number(p.is_active) === 1) : [];

        const productOptions = ['<option value="0">-- 請選擇商品 --</option>']
            .concat(products.map(p => `<option value="${p.product_id}">${escapeHtml(p.name)} (${escapeHtml(p.category || '未分類')})</option>`));

        const defaults = ['最多人購買', '店長推薦', '回購人氣王', '今日熱銷', '高評價商品', '限量精選'];

        tbody.innerHTML = '';
        for (let i = 0; i < 6; i++) {
            const rowConfig = configRows[i] || {};
            const pid = Number(rowConfig.product_id || 0);
            const badge = rowConfig.badge ? String(rowConfig.badge) : defaults[i];

            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>#${i + 1}</td>
                <td>
                    <select class="admin-input featured-product-select" data-index="${i}" style="width:100%;">
                        ${productOptions.join('')}
                    </select>
                </td>
                <td>
                    <input type="text" class="admin-input featured-product-badge" data-index="${i}" value="${escapeHtml(badge)}" maxlength="24" placeholder="例如：最多人購買">
                </td>
            `;
            tbody.appendChild(tr);
            const selectEl = tr.querySelector('.featured-product-select');
            if (selectEl) selectEl.value = String(pid);
        }

        const saveBtn = document.getElementById('saveFeaturedProductsBtn');
        if (saveBtn && !saveBtn.dataset.bound) {
            saveBtn.dataset.bound = '1';
            saveBtn.addEventListener('click', saveFeaturedProductsAdmin);
        }
    } catch (error) {
        tbody.innerHTML = '<tr><td colspan="3" style="color:#dc2626;">熱門商品設定載入失敗</td></tr>';
    }
}

async function saveFeaturedProductsAdmin() {
    const msg = document.getElementById('featuredProductsMsg');
    const rows = [];
    for (let i = 0; i < 6; i++) {
        const selectEl = document.querySelector(`.featured-product-select[data-index="${i}"]`);
        const badgeEl = document.querySelector(`.featured-product-badge[data-index="${i}"]`);
        rows.push({
            product_id: Number(selectEl?.value || 0),
            badge: String(badgeEl?.value || '').trim()
        });
    }

    try {
        const res = await opsApi.post('../api/admin.php?action=save_featured_products', {
            featured_json: JSON.stringify(rows)
        });

        if (res.success) {
            if (msg) {
                msg.textContent = '已儲存，首頁熱門商品已更新';
                msg.style.color = '#0f766e';
            }
        } else if (msg) {
            let detailText = '';
            if (res.details && typeof res.details === 'object') {
                const dbMsg = res.details.db ? `DB: ${res.details.db}` : '';
                const fileMsg = res.details.file ? `File: ${res.details.file}` : '';
                detailText = [dbMsg, fileMsg].filter(Boolean).join(' | ');
            }
            msg.textContent = detailText ? `${res.error || '儲存失敗'} (${detailText})` : (res.error || '儲存失敗');
            msg.style.color = '#dc2626';
        }
    } catch (error) {
        if (msg) {
            msg.textContent = '儲存失敗';
            msg.style.color = '#dc2626';
        }
    }
}

async function loadSidebarAdsAdmin() {
    const tbody = document.querySelector('#sidebarAdsAdminTable tbody');
    const msg = document.getElementById('sidebarAdsMsg');
    if (!tbody) return;

    if (msg) {
        msg.textContent = '';
        msg.style.color = '#64748b';
    }

    try {
        const data = await opsApi.get('../api/admin.php?action=get_sidebar_ads');
        const rows = Array.isArray(data.sidebar_ads) ? data.sidebar_ads : [];
        sidebarAdsState = Array.from({ length: 4 }, (_, i) => {
            const row = rows[i] || {};
            return {
                image_url: String(row.image_url || '').trim(),
                link_url: String(row.link_url || './products.php').trim() || './products.php',
                alt: String(row.alt || `側邊廣告 ${i + 1}`).trim() || `側邊廣告 ${i + 1}`
            };
        });

        tbody.innerHTML = '';
        for (let i = 0; i < 4; i++) {
            const row = sidebarAdsState[i] || {};
            const imageUrl = String(row.image_url || '').trim();
            const hasImage = imageUrl !== '';
            const preview = hasImage ? imageUrl : SIDEBAR_AD_PLACEHOLDER;
            const statusText = hasImage ? '已上架圖片' : '未上傳（前台顯示歡迎諮詢投廣）';

            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>#${i + 1}</td>
                <td>
                    <img src="${escapeHtml(preview)}" alt="預覽${i + 1}" class="sidebar-ad-preview" style="width:160px;height:90px;object-fit:cover;border-radius:8px;border:1px solid #e2e8f0;">
                </td>
                <td>
                    <span class="sidebar-ad-status" data-index="${i}" style="font-size:13px;color:${hasImage ? '#0f766e' : '#b45309'};">${escapeHtml(statusText)}</span>
                </td>
                <td>
                    <input type="file" accept="image/png,image/jpeg,image/webp,image/gif" class="sidebar-ad-file" data-index="${i}" style="margin-bottom:8px;">
                    <div style="display:flex;gap:8px;flex-wrap:wrap;">
                        <button type="button" class="btn btn-secondary sidebar-ad-upload-btn" data-index="${i}">上傳圖片</button>
                        <button type="button" class="btn btn-danger sidebar-ad-unpublish-btn" data-index="${i}" ${hasImage ? '' : 'disabled'}>下架圖片</button>
                    </div>
                </td>
            `;
            tbody.appendChild(tr);
        }

        tbody.querySelectorAll('.sidebar-ad-upload-btn').forEach(btn => {
            btn.addEventListener('click', function () {
                const idx = Number(btn.getAttribute('data-index') || 0);
                uploadSidebarAdImage(idx);
            });
        });

        tbody.querySelectorAll('.sidebar-ad-unpublish-btn').forEach(btn => {
            btn.addEventListener('click', function () {
                const idx = Number(btn.getAttribute('data-index') || 0);
                unpublishSidebarAdImage(idx);
            });
        });

        const saveBtn = document.getElementById('saveSidebarAdsBtn');
        if (saveBtn && !saveBtn.dataset.bound) {
            saveBtn.dataset.bound = '1';
            saveBtn.addEventListener('click', saveSidebarAdsAdmin);
        }
    } catch (error) {
        tbody.innerHTML = '<tr><td colspan="5" style="color:#dc2626;">側邊廣告設定載入失敗</td></tr>';
    }
}

async function persistSidebarAdsState() {
    const rows = Array.from({ length: 4 }, (_, i) => {
        const row = sidebarAdsState[i] || {};
        return {
            image_url: String(row.image_url || '').trim(),
            link_url: String(row.link_url || './products.php').trim() || './products.php',
            alt: String(row.alt || `側邊廣告 ${i + 1}`).trim() || `側邊廣告 ${i + 1}`
        };
    });

    return opsApi.post('../api/admin.php?action=save_sidebar_ads', {
        sidebar_json: JSON.stringify(rows)
    });
}

async function uploadSidebarAdImage(index) {
    const msg = document.getElementById('sidebarAdsMsg');
    const fileEl = document.querySelector(`.sidebar-ad-file[data-index="${index}"]`);
    const previewEl = document.querySelectorAll('.sidebar-ad-preview')[index];
    const statusEl = document.querySelector(`.sidebar-ad-status[data-index="${index}"]`);

    if (!fileEl || !fileEl.files || !fileEl.files[0]) {
        if (msg) {
            msg.textContent = '請先選擇圖片檔案';
            msg.style.color = '#dc2626';
        }
        return;
    }

    const fd = new FormData();
    fd.append('ad_image', fileEl.files[0]);

    try {
        if (msg) {
            msg.textContent = '圖片上傳中...';
            msg.style.color = '#64748b';
        }

        const response = await fetch('../api/admin.php?action=upload_sidebar_ad_image', {
            method: 'POST',
            credentials: 'include',
            body: fd
        });
        const result = await response.json();

        if (!result.success || !result.image_url) {
            let detail = '';
            if (Array.isArray(result.details) && result.details.length) {
                detail = '（' + result.details.join(' | ') + '）';
            }
            throw new Error((result.error || '上傳失敗') + detail);
        }

        if (!sidebarAdsState[index]) {
            sidebarAdsState[index] = {
                image_url: '',
                link_url: './products.php',
                alt: `側邊廣告 ${index + 1}`
            };
        }
        sidebarAdsState[index].image_url = result.image_url;

        const saveRes = await persistSidebarAdsState();
        if (!saveRes || !saveRes.success) {
            throw new Error((saveRes && saveRes.error) ? saveRes.error : '上傳後自動儲存失敗');
        }

        if (previewEl) {
            previewEl.src = result.image_url;
        }
        if (statusEl) {
            statusEl.textContent = '已上架圖片';
            statusEl.style.color = '#0f766e';
        }
        const unpublishBtn = document.querySelector(`.sidebar-ad-unpublish-btn[data-index="${index}"]`);
        if (unpublishBtn) {
            unpublishBtn.disabled = false;
        }
        if (msg) {
            msg.textContent = `第 ${index + 1} 格圖片已上傳並完成投放`; 
            msg.style.color = '#0f766e';
        }
    } catch (error) {
        if (msg) {
            msg.textContent = error.message || '上傳失敗';
            msg.style.color = '#dc2626';
        }
    }
}

async function unpublishSidebarAdImage(index) {
    const msg = document.getElementById('sidebarAdsMsg');
    const previewEl = document.querySelectorAll('.sidebar-ad-preview')[index];
    const statusEl = document.querySelector(`.sidebar-ad-status[data-index="${index}"]`);
    const unpublishBtn = document.querySelector(`.sidebar-ad-unpublish-btn[data-index="${index}"]`);

    const current = sidebarAdsState[index] || null;
    if (!current || !String(current.image_url || '').trim()) {
        if (msg) {
            msg.textContent = `第 ${index + 1} 格目前沒有已上架圖片`; 
            msg.style.color = '#b45309';
        }
        return;
    }

    if (!confirm(`確定要下架第 ${index + 1} 格圖片嗎？`)) {
        return;
    }

    try {
        sidebarAdsState[index].image_url = '';
        const saveRes = await persistSidebarAdsState();
        if (!saveRes || !saveRes.success) {
            throw new Error((saveRes && saveRes.error) ? saveRes.error : '下架後自動儲存失敗');
        }

        if (previewEl) {
            previewEl.src = SIDEBAR_AD_PLACEHOLDER;
        }
        if (statusEl) {
            statusEl.textContent = '未上傳（前台顯示歡迎諮詢投廣）';
            statusEl.style.color = '#b45309';
        }
        if (unpublishBtn) {
            unpublishBtn.disabled = true;
        }
        if (msg) {
            msg.textContent = `第 ${index + 1} 格圖片已下架`; 
            msg.style.color = '#0f766e';
        }
    } catch (error) {
        if (msg) {
            msg.textContent = error.message || '下架失敗';
            msg.style.color = '#dc2626';
        }
    }
}

async function saveSidebarAdsAdmin() {
    const msg = document.getElementById('sidebarAdsMsg');
    try {
        const res = await persistSidebarAdsState();

        if (res.success) {
            if (msg) {
                msg.textContent = '側邊廣告設定已同步';
                msg.style.color = '#0f766e';
            }
        } else if (msg) {
            msg.textContent = res.error || '儲存失敗';
            msg.style.color = '#dc2626';
        }
    } catch (error) {
        if (msg) {
            msg.textContent = '儲存失敗';
            msg.style.color = '#dc2626';
        }
    }
}

// 加載促銷列表
async function loadPromotions() {
    try {
        bindPromotionFormActions();
        const data = await opsApi.get('../api/admin.php?action=list_promotions');
        const rows = data.promotions || [];
        renderPromotionsTable(rows);
        await loadPromotionRuleConfig();
        await loadWheelPrizes();
    } catch (error) {
        console.error('加載促銷:', error);
        const tableBody = document.querySelector('#promotionsTable tbody');
        if (tableBody) {
            tableBody.innerHTML = '<tr><td colspan="6" style="color:#dc2626;">促銷載入失敗</td></tr>';
        }
    }
}

function formatPromoDate(value) {
    if (!value) return '-';
    const date = new Date(value.replace(' ', 'T'));
    if (Number.isNaN(date.getTime())) return value;
    const y = date.getFullYear();
    const m = String(date.getMonth() + 1).padStart(2, '0');
    const d = String(date.getDate()).padStart(2, '0');
    const hh = String(date.getHours()).padStart(2, '0');
    const mm = String(date.getMinutes()).padStart(2, '0');
    return `${y}-${m}-${d} ${hh}:${mm}`;
}

function toDatetimeLocal(value) {
    if (!value) return '';
    return value.replace(' ', 'T').slice(0, 16);
}

function promotionTypeLabel(type) {
    const map = {
        global_discount: '全館折扣',
        threshold_discount: '滿額折扣',
        add_on_purchase: '加價購',
        bundle: '組合優惠',
        free_shipping: '免運',
        coupon: '優惠碼'
    };
    return map[type] || type;
}

function renderPromotionsTable(rows) {
    const tbody = document.querySelector('#promotionsTable tbody');
    if (!tbody) return;
    tbody.innerHTML = '';

    if (!rows.length) {
        tbody.innerHTML = '<tr><td colspan="6" style="color:#64748b;">目前尚無促銷活動，可先新增一筆。</td></tr>';
        return;
    }

    rows.forEach((row) => {
        const tr = document.createElement('tr');
        const discountText = row.discount_type === 'percent'
            ? `${Number(row.discount_value || 0)}%`
            : `$${Number(row.discount_value || 0).toFixed(0)}`;
        const statusText = Number(row.is_active) === 1 ? '啟用中' : '已停用';
        const statusColor = Number(row.is_active) === 1 ? '#0f766e' : '#9ca3af';

        tr.innerHTML = `
            <td>${escapeHtml(row.title || '-')}</td>
            <td>${escapeHtml(promotionTypeLabel(row.promotion_type))}</td>
            <td>${escapeHtml(discountText)}</td>
            <td>${escapeHtml(formatPromoDate(row.start_date))}</td>
            <td><span style="color:${statusColor};font-weight:700;">${statusText}</span></td>
            <td style="display:flex;gap:8px;flex-wrap:wrap;">
                <button type="button" class="btn btn-secondary btn-sm" data-edit-promo="${row.promotion_id}">編輯</button>
                <button type="button" class="btn btn-primary btn-sm" data-toggle-promo="${row.promotion_id}" data-next="${Number(row.is_active) === 1 ? 0 : 1}">${Number(row.is_active) === 1 ? '停用' : '啟用'}</button>
            </td>
        `;
        tr.dataset.promotionJson = JSON.stringify(row);
        tbody.appendChild(tr);
    });

    tbody.querySelectorAll('[data-edit-promo]').forEach((btn) => {
        btn.addEventListener('click', () => {
            const tr = btn.closest('tr');
            if (!tr?.dataset.promotionJson) return;
            const row = JSON.parse(tr.dataset.promotionJson);
            openPromotionForm(row);
        });
    });

    tbody.querySelectorAll('[data-toggle-promo]').forEach((btn) => {
        btn.addEventListener('click', async () => {
            const promotionId = btn.getAttribute('data-toggle-promo');
            const next = btn.getAttribute('data-next');
            try {
                const res = await opsApi.post('../api/admin.php?action=toggle_promotion_status', {
                    promotion_id: promotionId,
                    is_active: next
                });
                if (!res.success) {
                    alert(res.error || '更新狀態失敗');
                    return;
                }
                await loadPromotions();
            } catch (error) {
                alert('更新狀態失敗');
            }
        });
    });
}

function setPromotionFormMessage(text, ok = false) {
    const msg = document.getElementById('promotionFormMsg');
    if (!msg) return;
    msg.textContent = text;
    msg.style.color = ok ? '#0f766e' : '#dc2626';
}

function resetPromotionForm() {
    const set = (id, value) => {
        const el = document.getElementById(id);
        if (el) el.value = value;
    };
    set('promoFormId', '');
    set('promoFormTitle', '');
    set('promoFormDesc', '');
    set('promoFormType', 'global_discount');
    set('promoFormDiscountType', 'percent');
    set('promoFormDiscountValue', '');
    set('promoFormMinAmount', '0');
    set('promoFormMaxDiscount', '');
    set('promoFormProductId', '');
    set('promoFormStartDate', '');
    set('promoFormEndDate', '');
    setPromotionFormMessage('');
}

function openPromotionForm(row = null) {
    const wrap = document.getElementById('promotionFormWrap');
    if (!wrap) return;
    wrap.style.display = 'block';
    if (!row) {
        resetPromotionForm();
        return;
    }

    const set = (id, value) => {
        const el = document.getElementById(id);
        if (el) el.value = value;
    };
    set('promoFormId', row.promotion_id || '');
    set('promoFormTitle', row.title || '');
    set('promoFormDesc', row.description || '');
    set('promoFormType', row.promotion_type || 'global_discount');
    set('promoFormDiscountType', row.discount_type || 'percent');
    set('promoFormDiscountValue', row.discount_value ?? '');
    set('promoFormMinAmount', row.min_amount ?? '0');
    set('promoFormMaxDiscount', row.max_discount ?? '');
    set('promoFormProductId', row.product_id ?? '');
    set('promoFormStartDate', toDatetimeLocal(row.start_date || ''));
    set('promoFormEndDate', toDatetimeLocal(row.end_date || ''));
    setPromotionFormMessage('');
}

async function submitPromotionForm() {
    const payload = {
        promotion_id: document.getElementById('promoFormId')?.value || '',
        title: document.getElementById('promoFormTitle')?.value?.trim() || '',
        description: document.getElementById('promoFormDesc')?.value?.trim() || '',
        promotion_type: document.getElementById('promoFormType')?.value || 'global_discount',
        discount_type: document.getElementById('promoFormDiscountType')?.value || 'percent',
        discount_value: document.getElementById('promoFormDiscountValue')?.value || '0',
        min_amount: document.getElementById('promoFormMinAmount')?.value || '0',
        max_discount: document.getElementById('promoFormMaxDiscount')?.value || '',
        product_id: document.getElementById('promoFormProductId')?.value || '',
        start_date: document.getElementById('promoFormStartDate')?.value || '',
        end_date: document.getElementById('promoFormEndDate')?.value || ''
    };

    if (!payload.title || !payload.start_date || !payload.end_date) {
        setPromotionFormMessage('請填寫標題、開始與結束時間');
        return;
    }

    try {
        const res = await opsApi.post('../api/admin.php?action=save_promotion', payload);
        if (!res.success) {
            setPromotionFormMessage(res.error || '儲存失敗');
            return;
        }
        setPromotionFormMessage(res.message || '儲存成功', true);
        await loadPromotions();
        const wrap = document.getElementById('promotionFormWrap');
        if (wrap) wrap.style.display = 'none';
        resetPromotionForm();
    } catch (error) {
        setPromotionFormMessage('儲存失敗');
    }
}

function bindPromotionFormActions() {
    const newBtn = document.getElementById('newPromotionBtn');
    const saveBtn = document.getElementById('savePromotionBtn');
    const cancelBtn = document.getElementById('cancelPromotionBtn');

    if (newBtn && !newBtn.dataset.bound) {
        newBtn.dataset.bound = '1';
        newBtn.addEventListener('click', () => openPromotionForm());
    }
    if (saveBtn && !saveBtn.dataset.bound) {
        saveBtn.dataset.bound = '1';
        saveBtn.addEventListener('click', submitPromotionForm);
    }
    if (cancelBtn && !cancelBtn.dataset.bound) {
        cancelBtn.dataset.bound = '1';
        cancelBtn.addEventListener('click', () => {
            const wrap = document.getElementById('promotionFormWrap');
            if (wrap) wrap.style.display = 'none';
            resetPromotionForm();
        });
    }
}

function showPromotionRuleMessage(text, isSuccess) {
    const el = document.getElementById('promotionRuleMsg');
    if (!el) return;
    el.textContent = text;
    el.className = 'message ' + (isSuccess ? 'success' : 'error');
    el.style.display = 'block';
    if (isSuccess) {
        setTimeout(() => {
            el.style.display = 'none';
        }, 2400);
    }
}

async function loadPromotionRuleConfig() {
    try {
        const data = await opsApi.get('../api/promotion_admin.php?action=get_config');
        if (!data.success) {
            showPromotionRuleMessage(data.error || '促銷規則載入失敗', false);
            return;
        }

        const shipping = data.shipping || {};
        const bundle = data.bundle || {};

        const setValue = (id, val, fallback) => {
            const input = document.getElementById(id);
            if (input) input.value = (val ?? fallback ?? '');
        };

        setValue('promoRuleHomeFee', shipping.home_fee?.value, '100');
        setValue('promoRuleHomeThreshold', shipping.home_threshold?.value, '499');
        setValue('promoRuleConvenienceFee', shipping.convenience_fee?.value, '60');
        setValue('promoRuleConvenienceThreshold', shipping.convenience_threshold?.value, '299');

        setValue('promoRuleBeverageQty', bundle.beverage_discount_qty?.value, '2');
        setValue('promoRuleBeveragePercent', bundle.beverage_discount_percent?.value, '12');
        setValue('promoRuleSnackQty', bundle.snack_discount_qty?.value, '3');
        setValue('promoRuleSnackFixed', bundle.snack_discount_fixed?.value, '20');

        bindPromotionRuleActions();
    } catch (error) {
        showPromotionRuleMessage('促銷規則載入失敗', false);
    }
}

function bindPromotionRuleActions() {
    const shippingBtn = document.getElementById('promoRuleSaveShippingBtn');
    const bundleBtn = document.getElementById('promoRuleSaveBundleBtn');

    if (shippingBtn && !shippingBtn.dataset.bound) {
        shippingBtn.dataset.bound = '1';
        shippingBtn.addEventListener('click', savePromotionShippingRules);
    }
    if (bundleBtn && !bundleBtn.dataset.bound) {
        bundleBtn.dataset.bound = '1';
        bundleBtn.addEventListener('click', savePromotionBundleRules);
    }
}

async function savePromotionShippingRules() {
    const payload = {
        home_fee: document.getElementById('promoRuleHomeFee')?.value || '',
        home_threshold: document.getElementById('promoRuleHomeThreshold')?.value || '',
        convenience_fee: document.getElementById('promoRuleConvenienceFee')?.value || '',
        convenience_threshold: document.getElementById('promoRuleConvenienceThreshold')?.value || ''
    };

    if (!payload.home_fee || !payload.home_threshold || !payload.convenience_fee || !payload.convenience_threshold) {
        showPromotionRuleMessage('請完整填寫運費設定', false);
        return;
    }

    try {
        const res = await opsApi.post('../api/promotion_admin.php?action=update_shipping', payload);
        if (res.success) {
            showPromotionRuleMessage('運費設定已更新', true);
        } else {
            showPromotionRuleMessage(res.error || '運費設定更新失敗', false);
        }
    } catch (error) {
        showPromotionRuleMessage('運費設定更新失敗', false);
    }
}

async function savePromotionBundleRules() {
    const payload = {
        beverage_qty: document.getElementById('promoRuleBeverageQty')?.value || '',
        beverage_percent: document.getElementById('promoRuleBeveragePercent')?.value || '',
        snack_qty: document.getElementById('promoRuleSnackQty')?.value || '',
        snack_fixed: document.getElementById('promoRuleSnackFixed')?.value || ''
    };

    if (!payload.beverage_qty || !payload.beverage_percent || !payload.snack_qty || !payload.snack_fixed) {
        showPromotionRuleMessage('請完整填寫組合優惠設定', false);
        return;
    }

    try {
        const res = await opsApi.post('../api/promotion_admin.php?action=update_bundle', payload);
        if (res.success) {
            showPromotionRuleMessage('組合優惠設定已更新', true);
        } else {
            showPromotionRuleMessage(res.error || '組合優惠設定更新失敗', false);
        }
    } catch (error) {
        showPromotionRuleMessage('組合優惠設定更新失敗', false);
    }
}

async function loadWheelPrizes() {
    const tbody = document.querySelector('#wheelPrizesTable tbody');
    if (!tbody) return;

    const msg = document.getElementById('wheelPrizesMsg');
    if (msg) {
        msg.textContent = '';
        msg.style.color = '';
    }

    try {
        const data = await opsApi.get('../api/admin.php?action=get_wheel_prizes');
        const prizes = data.prizes || [];
        tbody.innerHTML = '';

        if (prizes.length !== 8) {
            tbody.innerHTML = '<tr><td colspan="5" style="color:#dc2626;">獎項設定格式錯誤，請重新整理。</td></tr>';
            return;
        }

        prizes.forEach((prize, index) => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>#${index + 1}</td>
                <td><input type="text" class="wheel-name" value="${escapeHtml(prize.name)}" style="width:100%;"></td>
                <td>
                    <select class="wheel-type" style="width:100%;">
                        <option value="percent" ${prize.discount_type === 'percent' ? 'selected' : ''}>percent</option>
                        <option value="fixed" ${prize.discount_type === 'fixed' ? 'selected' : ''}>fixed</option>
                    </select>
                </td>
                <td><input type="number" class="wheel-value" min="0" step="0.01" value="${Number(prize.value || 0)}" style="width:100%;"></td>
                <td><input type="number" class="wheel-min" min="0" step="0.01" value="${Number(prize.min_purchase || 0)}" style="width:100%;"></td>
            `;
            tbody.appendChild(tr);
        });

        const saveBtn = document.getElementById('saveWheelPrizesBtn');
        if (saveBtn) {
            saveBtn.onclick = saveWheelPrizes;
        }
    } catch (error) {
        tbody.innerHTML = '<tr><td colspan="5" style="color:#dc2626;">載入轉盤設定失敗</td></tr>';
    }
}

async function saveWheelPrizes() {
    const rows = Array.from(document.querySelectorAll('#wheelPrizesTable tbody tr'));
    const msg = document.getElementById('wheelPrizesMsg');
    if (!rows.length) return;

    const prizes = rows.map((tr, index) => ({
        id: index,
        name: tr.querySelector('.wheel-name')?.value?.trim() || '',
        discount_type: tr.querySelector('.wheel-type')?.value === 'percent' ? 'percent' : 'fixed',
        value: Number(tr.querySelector('.wheel-value')?.value || 0),
        min_purchase: Number(tr.querySelector('.wheel-min')?.value || 0)
    }));

    if (prizes.some(p => !p.name)) {
        if (msg) {
            msg.textContent = '每個獎項都必須有名稱';
            msg.style.color = '#dc2626';
        }
        return;
    }

    try {
        const res = await opsApi.post('../api/admin.php?action=save_wheel_prizes', {
            prizes_json: JSON.stringify(prizes)
        });

        if (res.success) {
            if (msg) {
                msg.textContent = '已儲存，前台轉盤已更新';
                msg.style.color = '#0f766e';
            }
        } else if (msg) {
            msg.textContent = res.error || '儲存失敗';
            msg.style.color = '#dc2626';
        }
    } catch (error) {
        if (msg) {
            msg.textContent = '儲存失敗';
            msg.style.color = '#dc2626';
        }
    }
}

// 加載庫存列表
async function loadInventory() {
    try {
        if (typeof window.adminInventoryRefresh === 'function') {
            window.adminInventoryRefresh();
        }
    } catch (error) {
        console.error('加載庫存:', error);
    }
}

// 加載客服聊天（如果有 admin_chat.js，則由 admin_chat.js 處理）
async function loadChat() {
    // 此功能由 admin_chat.js 處理
    console.log('客服系統已就緒');
}

// 從網址參數切換頁面
(function() {
    const params = new URLSearchParams(window.location.search);
    const page = params.get('page') || 'dashboard';
    const target = document.querySelector(`.admin-nav-item[data-page="${page}"]`);
    if (target) target.click();
})();
</script>

<script src="../public/js/admin_inventory.js"></script>
<script src="../public/js/admin_chat.js"></script>

<style>
    /* 管理員頁面容器 */
    .admin-container {
        display: flex;
        flex-direction: column;
        min-height: calc(100vh - 80px);
    }

    /* 主要內容區 */
    .admin-main {
        flex: 1;
        display: flex;
        flex-direction: column;
        background: #f3f7fb;
    }

    .admin-content {
        flex: 1;
        padding: 24px;
    }

    .admin-page {
        display: none;
        animation: fadeIn 0.2s ease;
    }

    .admin-page.active,
    .admin-page[style*="display: block"] {
        display: block;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
        }
        to {
            opacity: 1;
        }
    }

    /* 统计卡片 */
    .admin-stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 16px;
        margin-bottom: 24px;
    }

    .stat-card {
        background: #fff;
        padding: 16px;
        border-radius: 10px;
        border: 1px solid rgba(141, 195, 214, 0.2);
        display: flex;
        gap: 12px;
        align-items: center;
    }

    .stat-icon {
        font-size: 2rem;
    }

    .stat-label {
        margin: 0;
        font-size: 0.85rem;
        color: #666;
    }

    .stat-number {
        margin: 0;
        font-size: 1.5rem;
        font-weight: 700;
        color: #0f766e;
    }

    /* 卡片容器 */
    .admin-card {
        background: #fff;
        padding: 20px;
        border-radius: 10px;
        border: 1px solid rgba(141, 195, 214, 0.2);
        margin-bottom: 16px;
    }

    .admin-card h3 {
        margin-top: 0;
        margin-bottom: 16px;
        color: #0f172a;
    }

    /* 表格樣式 */
    .admin-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.9rem;
    }

    .admin-table th {
        background: #f8fbff;
        padding: 12px;
        text-align: left;
        border-bottom: 2px solid rgba(141, 195, 214, 0.3);
        font-weight: 700;
        color: #0f172a;
    }

    .admin-table td {
        padding: 12px;
        border-bottom: 1px solid rgba(141, 195, 214, 0.15);
    }

    .admin-table tr:hover {
        background: #f8fbff;
    }

    .btn {
        padding: 6px 12px;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        font-size: 0.85rem;
        transition: all 0.2s ease;
    }

    .btn-sm {
        font-size: 0.75rem;
        padding: 4px 8px;
    }

    .btn-primary {
        background: linear-gradient(130deg, #0f766e, #14b8a6);
        color: #fff;
    }

    .btn-primary:hover {
        opacity: 0.85;
    }

    .refund-admin-status {
        display: inline-flex;
        align-items: center;
        padding: 4px 10px;
        border-radius: 999px;
        font-size: 0.78rem;
        font-weight: 700;
        border: 1px solid transparent;
    }

    .refund-admin-pending {
        color: #1d4ed8;
        background: #eff6ff;
        border-color: #bfdbfe;
    }

    .refund-admin-approved {
        color: #166534;
        background: #f0fdf4;
        border-color: #bbf7d0;
    }

    .refund-admin-rejected {
        color: #991b1b;
        background: #fef2f2;
        border-color: #fecaca;
    }

    .refund-admin-actions {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
    }

    /* 表格編輯行 */
    .admin-table input[type="text"],
    .admin-table input[type="number"],
    .admin-table input[type="checkbox"] {
        padding: 6px;
        border: 1px solid #ccc;
        border-radius: 4px;
        font-size: 0.9rem;
    }

    .admin-table input[type="text"],
    .admin-table input[type="number"] {
        background: #f9f9f9;
        color: #333;
    }

    .admin-table input[type="text"]:focus,
    .admin-table input[type="number"]:focus {
        background: #fff;
        border-color: #0f766e;
        outline: none;
    }

    .admin-table input[type="checkbox"] {
        cursor: pointer;
        width: 18px;
        height: 18px;
    }

    .admin-table img {
        vertical-align: middle;
    }

    /* 響應式設計 */
    @media (max-width: 768px) {
        .admin-content {
            padding: 12px;
        }

        .admin-table {
            font-size: 0.8rem;
        }

        .admin-table th,
        .admin-table td {
            padding: 8px;
        }
    }
</style>
<?php endif; ?>
<?php
$disableChatWidget = true;
require_once __DIR__ . '/layout_footer.php';
?>
