<?php require_once __DIR__ . '/layout_header.php'; ?>
<?php if (!$currentUser || ($currentUser['role'] ?? '') !== 'admin'): ?>
    <div class="card">
        <p>您沒有權限訪問此頁面。</p>
    </div>
<?php else: ?>

<div class="admin-container">
    <!-- 側邊欄導航 -->
    <aside class="admin-sidebar">
        <div class="admin-logo">
            <h2>🏢 VR Mall</h2>
            <p>管理後臺</p>
        </div>
        
        <nav class="admin-nav">
            <a href="#" class="admin-nav-item active" data-page="dashboard">
                <span class="icon">📊</span>
                <span>儀表板</span>
            </a>
            <a href="#" class="admin-nav-item" data-page="orders">
                <span class="icon">📋</span>
                <span>訂單管理</span>
            </a>
            <a href="#" class="admin-nav-item" data-page="products">
                <span class="icon">📦</span>
                <span>商品管理</span>
            </a>
            <a href="#" class="admin-nav-item" data-page="inventory">
                <span class="icon">📚</span>
                <span>庫存管理</span>
            </a>
            <a href="#" class="admin-nav-item" data-page="chat">
                <span class="icon">💬</span>
                <span>客服系統</span>
            </a>
        </nav>
    </aside>

    <!-- 主要內容區 -->
    <main class="admin-main">
        <!-- 頂部導航欄 -->
        <header class="admin-topbar">
            <div class="admin-topbar-left">
                <button class="admin-menu-toggle" id="sidebarToggle">☰</button>
                <h1 id="pageTitle">儀表板</h1>
            </div>
            <div class="admin-topbar-right">
                <button class="admin-topbar-icon" title="通知">
                    <span>🔔</span>
                </button>
                <button class="admin-topbar-icon" title="用戶">
                    <span>👤</span>
                </button>
                <button class="admin-topbar-icon" title="設定">
                    <span>⚙️</span>
                </button>
                <button class="admin-topbar-icon logout" id="logoutBtn" title="登出">
                    <span>🚪</span>
                </button>
            </div>
        </header>

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
                            <p class="stat-label">總收入</p>
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
                        <input type="text" placeholder="搜尋訂單..." class="admin-input">
                        <select class="admin-select">
                            <option>所有狀態</option>
                            <option>pending</option>
                            <option>preparing</option>
                            <option>shipping</option>
                            <option>done</option>
                        </select>
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
                                <label>販売価格 ($) *</label>
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
        </section>
    </main>
</div>

<script src="../public/js/admin_chat.js"></script>
<script src="../public/js/admin_inventory.js"></script>

<script>
// 頁面導航邏輯
document.querySelectorAll('.admin-nav-item').forEach(item => {
    item.addEventListener('click', (e) => {
        e.preventDefault();
        const page = item.getAttribute('data-page');
        
        // 更新導航
        document.querySelectorAll('.admin-nav-item').forEach(i => i.classList.remove('active'));
        item.classList.add('active');
        
        // 更新頁面標題
        const titles = {
            dashboard: '儀表板',
            orders: '訂單管理',
            products: '商品管理',
            inventory: '庫存管理',
            chat: '客服系統'
        };
        document.getElementById('pageTitle').textContent = titles[page] || '儀表板';
        
        // 顯示對應頁面
        document.querySelectorAll('.admin-page').forEach(p => p.classList.remove('active'));
        document.getElementById(page + 'Page').classList.add('active');
    });
});

// 側邊欄切換（手機用）
document.getElementById('sidebarToggle').addEventListener('click', () => {
    document.querySelector('.admin-sidebar').classList.toggle('active');
});
</script>

<script src="../public/js/admin_inventory.js"></script>
<script src="../public/js/admin_chat.js"></script>

<?php endif; ?>
<?php
$disableChatWidget = true;
require_once __DIR__ . '/layout_footer.php';
?>
