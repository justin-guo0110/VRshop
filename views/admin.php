<?php require_once __DIR__ . '/layout_header.php'; ?>
<?php if (!$currentUser || ($currentUser['role'] ?? '') !== 'admin'): ?>
    <div class="card">
        <p>您沒有權限訪問此頁面。</p>
    </div>
<?php else: ?>
    <section class="card">
        <div class="tabs">
            <button class="tab-button active" data-target="dashboardTab">儀表板</button>
            <button class="tab-button" data-target="ordersTab">訂單</button>
            <button class="tab-button" data-target="productsTab">商品</button>
            <button class="tab-button" data-target="inventoryTab">庫存</button>
            <button class="tab-button" data-target="chatTab">客服</button>
        </div>
        <div id="dashboardTab" class="tab-content active">
            <div class="grid two-cols" id="dashboardCards">
                <div class="card small">
                    <h3>待處理/準備中</h3>
                    <p id="statPending" class="stat-number">0</p>
                </div>
                <div class="card small">
                    <h3>今日訂單</h3>
                    <p id="statTodayOrders" class="stat-number">0</p>
                </div>
                <div class="card small">
                    <h3>今日收入</h3>
                    <p id="statTodayRevenue" class="stat-number">$0.00</p>
                </div>
                <div class="card small">
                    <h3>低庫存 (&lt;10)</h3>
                    <p id="statLowStock" class="stat-number">0</p>
                </div>
            </div>
        </div>
        <div id="ordersTab" class="tab-content">
            <table class="data-table" id="ordersTable">
                <thead>
                    <tr>
                        <th>ID</th><th>顧客</th><th>目前狀態</th><th>總金額</th><th>成立時間</th><th>訂單內容</th><th>更新狀態</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
        <div id="productsTab" class="tab-content">
            <table class="data-table" id="productsTable">
                <thead>
                    <tr>
                        <th>ID</th><th>	商品名稱</th><th>分類</th><th>價格</th><th>庫存</th><th>上架</th><th>儲存</th><th>操作</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
        <div id="inventoryTab" class="tab-content">
            <div class="grid two-cols">
                <div>
                    <h3>新增進貨</h3>
                    <form id="receivingForm" class="form-grid">
                        <label>商品
                            <select name="product_id" id="receivingProduct"></select>
                        </label>
                        <label>數量
                            <input type="number" name="qty" id="receivingQty" min="1" value="1">
                        </label>
                        <label>供應商
                            <input type="text" name="supplier_name" id="receivingSupplier">
                        </label>
                        <label>單價
                            <input type="number" step="0.01" min="0" name="unit_cost" id="receivingUnitCost">
                        </label>
                        <label>備註
                            <textarea name="note" id="receivingNote"></textarea>
                        </label>
                        <label>進貨時間
                            <input type="datetime-local" id="receivingAt">
                        </label>
                        <button type="submit" class="btn">儲存</button>
                        <div id="receivingMessage" class="message"></div>
                    </form>
                </div>
                <div>

                    <h3>最近進貨</h3>
                    <table class="data-table" id="receivingTable">
                        <thead>
                            <tr>
                                <th>ID</th><th>供應商</th><th>項目數</th><th>總金額</th><th>進貨時間</th><th>備註</th><th>操作</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                    <div id="receivingDetail"></div>
                </div>
            </div>
            <div style="margin-top:20px;">

                <h3>庫存異動</h3>
                <table class="data-table" id="movementsTable">
                    <thead>
                        <tr>
                            <th>ID</th><th>商品</th><th>類型</th><th>異動數量</th><th>參考</th><th>備註</th><th>建立時間</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
        <div id="chatTab" class="tab-content">
            <div style="display:flex; height:600px; border:1px solid #ddd; border-radius:8px; overflow:hidden;">
                <div style="width:250px; border-right:1px solid #ddd; overflow-y:auto;" id="adminChatList">
                    <!-- Chat list will be rendered here -->
                </div>
                <div style="flex:1; display:flex; flex-direction:column;">
                    <div style="padding:15px; border-bottom:1px solid #ddd; font-weight:bold; background:#f9f9f9;" id="chatTitle">
                        Select a chat
                    </div>
                    <div style="flex:1; padding:20px; overflow-y:auto; background:#fff;" id="adminChatMessages">
                        <!-- Messages will be rendered here -->
                    </div>
                    <div style="padding:15px; border-top:1px solid #ddd; display:flex; gap:10px; background:#f9f9f9;">
                        <input type="text" id="adminChatInput" placeholder="Type a reply..." style="flex:1; padding:10px; border:1px solid #ddd; border-radius:4px;">
                        <button id="adminChatSendBtn" style="padding:10px 20px; background:#667eea; color:white; border:none; border-radius:4px; cursor:pointer;">送出</button>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <script src="../public/js/admin_chat.js"></script>
    <script src="../public/js/admin_inventory.js"></script>
<?php endif; ?>
<?php
$disableChatWidget = true;
require_once __DIR__ . '/layout_footer.php';
?>
