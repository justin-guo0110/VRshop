<?php require_once __DIR__ . '/layout_header.php'; ?>
<?php if (!$currentUser || ($currentUser['role'] ?? '') !== 'admin'): ?>
    <div class="card">
        <p>Admin access only.</p>
    </div>
<?php else: ?>
    <section class="card">
        <div class="tabs">
            <button class="tab-button" data-target="dashboardTab">Dashboard</button>
            <button class="tab-button active" data-target="ordersTab">Orders</button>
            <button class="tab-button" data-target="productsTab">Products</button>
            <button class="tab-button" data-target="inventoryTab">Inventory</button>
            <button class="tab-button" data-target="chatTab">Support</button>
        </div>
        <div id="dashboardTab" class="tab-content">
            <div class="grid two-cols" id="dashboardCards">
                <div class="card small">
                    <h3>Pending/Preparing</h3>
                    <p id="statPending" class="stat-number">0</p>
                </div>
                <div class="card small">
                    <h3>Today Orders</h3>
                    <p id="statTodayOrders" class="stat-number">0</p>
                </div>
                <div class="card small">
                    <h3>Today Revenue</h3>
                    <p id="statTodayRevenue" class="stat-number">$0.00</p>
                </div>
                <div class="card small">
                    <h3>Low Stock (&lt;10)</h3>
                    <p id="statLowStock" class="stat-number">0</p>
                </div>
            </div>
        </div>
        <div id="ordersTab" class="tab-content active">
            <table class="data-table" id="ordersTable">
                <thead>
                    <tr>
                        <th>ID</th><th>Customer</th><th>Status</th><th>Total</th><th>Created</th><th>Items</th><th>Update</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
        <div id="productsTab" class="tab-content">
            <table class="data-table" id="productsTable">
                <thead>
                    <tr>
                        <th>ID</th><th>Name</th><th>Category</th><th>Price</th><th>Stock</th><th>Active</th><th>Save</th><th>Inventory</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
        <div id="inventoryTab" class="tab-content">
            <div class="grid two-cols">
                <div>
                    <h3>Receiving</h3>
                    <form id="receivingForm" class="form-grid">
                        <label>Product
                            <select name="product_id" id="receivingProduct"></select>
                        </label>
                        <label>Quantity
                            <input type="number" name="qty" id="receivingQty" min="1" value="1">
                        </label>
                        <label>Supplier
                            <input type="text" name="supplier_name" id="receivingSupplier">
                        </label>
                        <label>Unit Cost
                            <input type="number" step="0.01" min="0" name="unit_cost" id="receivingUnitCost">
                        </label>
                        <label>Note
                            <textarea name="note" id="receivingNote"></textarea>
                        </label>
                        <label>Received At
                            <input type="datetime-local" id="receivingAt">
                        </label>
                        <button type="submit" class="btn">Save Receiving</button>
                        <div id="receivingMessage" class="message"></div>
                    </form>
                </div>
                <div>
                    <h3>Recent Receiving</h3>
                    <table class="data-table" id="receivingTable">
                        <thead>
                            <tr>
                                <th>ID</th><th>Supplier</th><th>Lines</th><th>Total</th><th>Received At</th><th>Note</th><th>View</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                    <div id="receivingDetail"></div>
                </div>
            </div>
            <div style="margin-top:20px;">
                <h3>Stock Movements</h3>
                <div style="margin-bottom:10px;">
                    <label>Filter by Product ID <input type="number" id="movementFilterProduct" style="width:120px;"></label>
                    <button id="movementFilterBtn" class="btn btn-secondary btn-sm">Filter</button>
                    <button id="movementClearBtn" class="btn btn-secondary btn-sm">Clear</button>
                </div>
                <table class="data-table" id="movementsTable">
                    <thead>
                        <tr>
                            <th>ID</th><th>Product</th><th>Type</th><th>Delta</th><th>Ref</th><th>Note</th><th>Created</th>
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
                        <button id="adminChatSendBtn" style="padding:10px 20px; background:#667eea; color:white; border:none; border-radius:4px; cursor:pointer;">Send</button>
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
