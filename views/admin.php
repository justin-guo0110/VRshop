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
    </section>
<?php endif; ?>
<?php require_once __DIR__ . '/layout_footer.php'; ?>
