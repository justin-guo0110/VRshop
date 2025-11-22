<?php require_once __DIR__ . '/layout_header.php'; ?>
<?php if (!$currentUser || ($currentUser['role'] ?? '') !== 'admin'): ?>
    <div class="card">
        <p>Admin access required.</p>
    </div>
<?php else: ?>
    <section class="card">
        <div class="tabs">
            <button class="tab-button active" data-target="ordersTab">Orders</button>
            <button class="tab-button" data-target="productsTab">Products</button>
        </div>
        <div id="ordersTab" class="tab-content active">
            <table class="data-table" id="ordersTable">
                <thead>
                    <tr>
                        <th>ID</th><th>Member</th><th>Status</th><th>Total</th><th>Created</th><th>Items</th><th>Action</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
        <div id="productsTab" class="tab-content">
            <table class="data-table" id="productsTable">
                <thead>
                    <tr>
                        <th>ID</th><th>Name</th><th>Category</th><th>Price</th><th>Stock</th><th>Active</th><th>Action</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </section>
<?php endif; ?>
<?php require_once __DIR__ . '/layout_footer.php'; ?>
