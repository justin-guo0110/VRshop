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
                        <button type="button" class="order-filter-btn" data-status="pending">已接單</button>
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
                                <input type="number" name="qty" min="1" value="1">
                            </div>
                            <div class="form-group">
                                <label>供應商</label>
                                <input type="text" name="supplier_name">
                            </div>
                            <div class="form-group">
                                <label>單價</label>
                                <input type="number" step="0.01" min="0" name="unit_cost">
                            </div>
                            <button type="submit" class="btn btn-primary">儲存</button>
                        </form>
                    </div>

                    <div class="admin-card">
                        <h3>📚 近期進貨</h3>
                        <table class="admin-table" id="receivingTable">
                            <thead>
                                <tr>
                                    <th>ID</th><th>供應商</th><th>項目</th><th>日期</th><th>操作</th>
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

            <!-- 促銷管理 -->
            <div id="promotionsPage" class="admin-page" style="display:none;">
                <div class="admin-card">
                    <div class="admin-toolbar"><h3>🎁 促銷活動</h3><button class="btn btn-primary">+ 新建促銷</button></div>
                    <table class="admin-table" id="promotionsTable">
                        <thead><tr><th>名稱</th><th>類型</th><th>折扣</th><th>開始日期</th><th>狀態</th><th>操作</th></tr></thead>
                        <tbody></tbody>
                    </table>
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

<script src="../public/js/admin_chat.js"></script>
<script src="../public/js/admin_inventory.js"></script>

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
    pending: '已接單',
    preparing: '準備中',
    shipping: '運送中',
    done: '已完成'
};

let currentOrderStatus = 'all';
let currentOrderKeyword = '';

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
        else if (page === 'inventory') loadInventory();
        else if (page === 'customers') loadCustomers();
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
            const status = o.status || 'pending';
            tr.innerHTML = `
                <td>#${escapeHtml(o.order_id)}</td>
                <td>${escapeHtml(o.name || o.email || '-')}</td>
                <td><span class="badge">${escapeHtml(orderStatusLabels[status] || status)}</span></td>
                <td>$${Number(o.total_amount || 0).toFixed(2)}</td>
                <td>${escapeHtml(o.created_at || '-')}</td>
                <td>
                    <select class="order-status-select" data-order-id="${escapeHtml(o.order_id)}" style="margin-right:5px;">
                        ${['pending', 'preparing', 'shipping', 'done'].map(s => `<option value="${s}" ${s === status ? 'selected' : ''}>${orderStatusLabels[s]}</option>`).join('')}
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

// 加載促銷列表
async function loadPromotions() {
    try {
        const tableBody = document.querySelector('#promotionsTable tbody');
        if (tableBody) {
            tableBody.innerHTML = '<tr><td colspan="6" style="color:#64748b;">目前未串接促銷列表（可先使用下方轉盤設定）。</td></tr>';
        }
        await loadWheelPrizes();
    } catch (error) {
        console.error('加載促銷:', error);
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
        // 臨時實現
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
