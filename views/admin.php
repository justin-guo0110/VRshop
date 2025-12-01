<?php require_once __DIR__ . '/layout_header.php'; ?>
<?php if (!$currentUser || ($currentUser['role'] ?? '') !== 'admin'): ?>
    <div class="card">
        <p>需要管理員權限。</p>
    </div>
<?php else: ?>
    <section class="card">
        <div class="tabs">
            <button class="tab-button active" data-target="ordersTab">訂單</button>
            <button class="tab-button" data-target="productsTab">商品</button>
            <button class="tab-button" data-target="chatTab">客服</button>
        </div>
        <div id="ordersTab" class="tab-content active">
            <table class="data-table" id="ordersTable">
                <thead>
                    <tr>
                        <th>ID</th><th>成員</th><th>狀態</th><th>總計</th><th>建立</th><th>專案</th><th>操作</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
        <div id="productsTab" class="tab-content">
            <table class="data-table" id="productsTable">
                <thead>
                    <tr>
                        <th>ID</th><th>姓名</th><th>類別</th><th>價格</th><th>庫存</th><th>在售</th><th>操作</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
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
                        <button id="adminChatSendBtn" style="padding:10px 20px; background:#667eea; color:white; border:none; border-radius:4px; cursor:pointer;">Send</button>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <script src="../public/js/admin_chat.js"></script>
<?php endif; ?>
<?php require_once __DIR__ . '/layout_footer.php'; ?>
