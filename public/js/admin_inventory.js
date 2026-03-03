(() => {
    const apiUrl = '../api/admin.php';

    const els = {
        statPending: document.getElementById('statPending'),
        statTodayOrders: document.getElementById('statTodayOrders'),
        statTodayRevenue: document.getElementById('statTodayRevenue'),
        statLowStock: document.getElementById('statLowStock'),
        receivingProduct: document.getElementById('receivingProduct'),
        receivingQty: document.getElementById('receivingQty'),
        receivingSupplier: document.getElementById('receivingSupplier'),
        receivingUnitCost: document.getElementById('receivingUnitCost'),
        receivingNote: document.getElementById('receivingNote'),
        receivingAt: document.getElementById('receivingAt'),
        receivingForm: document.getElementById('receivingForm'),
        receivingMessage: document.getElementById('receivingMessage'),
        receivingTable: document.getElementById('receivingTable'),
        receivingDetail: document.getElementById('receivingDetail'),
        movementsTable: document.getElementById('movementsTable'),
        movementFilterProduct: document.getElementById('movementFilterProduct'),
        movementFilterBtn: document.getElementById('movementFilterBtn'),
        movementClearBtn: document.getElementById('movementClearBtn'),
        productsTable: document.getElementById('productsTable')
    };

    function init() {
        if (!document.getElementById('inventoryTab') && !document.getElementById('dashboardTab')) return;
        loadDashboard();
        loadReceivingProducts();
        loadReceivingList();
        loadMovements();
        bindEvents();
    }

    function bindEvents() {
        if (els.receivingForm) {
            els.receivingForm.addEventListener('submit', onReceivingSubmit);
        }
        if (els.receivingTable) {
            els.receivingTable.addEventListener('click', (e) => {
                const viewId = e.target.getAttribute('data-view');
                if (viewId) {
                    loadReceivingDetail(viewId);
                }
            });
        }
        if (els.movementFilterBtn) {
            els.movementFilterBtn.addEventListener('click', (e) => {
                e.preventDefault();
                const pid = parseInt(els.movementFilterProduct.value || '0', 10);
                loadMovements(pid > 0 ? pid : null);
            });
        }
        if (els.movementClearBtn) {
            els.movementClearBtn.addEventListener('click', (e) => {
                e.preventDefault();
                if (els.movementFilterProduct) els.movementFilterProduct.value = '';
                loadMovements();
            });
        }
        if (els.productsTable) {
            els.productsTable.addEventListener('click', (e) => {
                const restockId = e.target.getAttribute('data-restock');
                const adjustId = e.target.getAttribute('data-adjust');
                if (restockId) {
                    switchToInventory(restockId);
                }
                if (adjustId) {
                    handleAdjust(adjustId);
                }
            });
        }
    }

    async function loadDashboard() {
        try {
            const res = await api.get(`${apiUrl}?action=dashboard_stats`);
            const stats = res.stats || {};
            if (els.statPending) els.statPending.textContent = stats.pending_count ?? 0;
            if (els.statTodayOrders) els.statTodayOrders.textContent = stats.today_orders_count ?? 0;
            if (els.statTodayRevenue) els.statTodayRevenue.textContent = `$${Number(stats.today_revenue || 0).toFixed(2)}`;
            if (els.statLowStock) els.statLowStock.textContent = stats.low_stock_count ?? 0;
        } catch (err) {
            console.error('載入儀表板統計失敗', err);
        }
    }

    async function loadReceivingProducts(selectedId) {
        if (!els.receivingProduct) return;
        try {
            const res = await api.get(`${apiUrl}?action=receiving_products`);
            const products = res.products || [];
            els.receivingProduct.innerHTML = products.map(p => `<option value="${p.product_id}">${p.name} (Stock: ${p.stock})</option>`).join('');
            if (selectedId) {
                els.receivingProduct.value = selectedId;
            }
        } catch (err) {
            console.error('載入進貨產品失敗', err);
        }
    }

    async function onReceivingSubmit(e) {
        e.preventDefault();
        if (!els.receivingProduct) return;
        const formData = {
            product_id: els.receivingProduct.value,
            qty: els.receivingQty ? els.receivingQty.value : 0,
            supplier_name: els.receivingSupplier ? els.receivingSupplier.value.trim() : '',
            unit_cost: els.receivingUnitCost ? els.receivingUnitCost.value : '',
            note: els.receivingNote ? els.receivingNote.value.trim() : ''
        };
        if (els.receivingAt && els.receivingAt.value) {
            formData.received_at = els.receivingAt.value.replace('T', ' ') + ':00';
        }
        try {
            const res = await api.post(`${apiUrl}?action=receiving_create`, formData);
            if (res.success) {
                showMessage('進貨已儲存', true);
                updateRowStock(formData.product_id, parseInt(formData.qty, 10));
                loadReceivingList();
                loadMovements();
                loadDashboard();
                els.receivingForm.reset();
            } else {
                showMessage(res.error || '進貨儲存失敗', false);
            }
        } catch (err) {
            console.error(err);
            showMessage('進貨儲存失敗', false);
        }
    }

    function showMessage(text, ok) {
        if (!els.receivingMessage) return;
        els.receivingMessage.textContent = text;
        els.receivingMessage.className = 'message ' + (ok ? 'success' : 'error');
    }

    async function loadReceivingList() {
        if (!els.receivingTable) return;
        try {
            const res = await api.get(`${apiUrl}?action=receiving_list`);
            const tbody = els.receivingTable.querySelector('tbody');
            tbody.innerHTML = '';
            (res.receivings || []).forEach(r => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>${r.receiving_id}</td>
                    <td>${r.supplier_name || ''}</td>
                    <td>${r.total_lines}</td>
                    <td>${r.total_cost !== null ? Number(r.total_cost).toFixed(2) : '-'}</td>
                    <td>${r.received_at}</td>
                    <td>${r.note || ''}</td>
                    <td><button class="btn btn-sm btn-secondary" data-view="${r.receiving_id}">明細</button></td>
                `;
                tbody.appendChild(tr);
            });
        } catch (err) {
            console.error('載入進貨列表失敗', err);
        }
    }

    async function loadReceivingDetail(id) {
        if (!els.receivingDetail) return;
        try {
            const res = await api.get(`${apiUrl}?action=receiving_detail&receiving_id=${encodeURIComponent(id)}`);
            if (!res.header) {
                els.receivingDetail.textContent = res.error || '無法載入進貨明細';
                return;
            }
            const items = res.items || [];
            let html = `
                <div class="card small">
                    <p><strong>供應商：</strong> ${res.header.supplier_name || '-'}</p>
                    <p><strong>進貨時間：</strong> ${res.header.received_at}</p>
                    <p><strong>備註：</strong> ${res.header.note || ''}</p>
                </div>
                <table class="data-table">
                    <thead><tr><th>商品名稱</th><th>數量</th><th>單價</th><th>小計</th></tr></thead>
                    <tbody>
                        ${items.map(i => `<tr><td>${i.name}</td><td>${i.qty}</td><td>${i.unit_cost !== null ? Number(i.unit_cost).toFixed(2) : '-'}</td><td>${i.subtotal_cost !== null ? Number(i.subtotal_cost).toFixed(2) : '-'}</td></tr>`).join('')}
                    </tbody>
                </table>
            `;
            els.receivingDetail.innerHTML = html;
        } catch (err) {
            console.error('載入進貨明細失敗', err);
        }
    }

    async function loadMovements(productId = null) {
        if (!els.movementsTable) return;
        try {
            const qs = productId ? `&product_id=${encodeURIComponent(productId)}` : '';
            const res = await api.get(`${apiUrl}?action=stock_movements_list${qs}`);
            const tbody = els.movementsTable.querySelector('tbody');
            tbody.innerHTML = '';
            (res.movements || []).forEach(m => {
                const ref = m.ref_type && m.ref_id ? `${m.ref_type} #${m.ref_id}` : m.ref_type || '';
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>${m.movement_id}</td>
                    <td>${m.name || ''} (#${m.product_id})</td>
                    <td>${m.movement_type}</td>
                    <td>${m.delta}</td>
                    <td>${ref}</td>
                    <td>${m.note || ''}</td>
                    <td>${m.created_at}</td>
                `;
                tbody.appendChild(tr);
            });
        } catch (err) {
            console.error('載入失敗', err);
        }
    }

    function switchToInventory(productId) {
        const tabBtn = document.querySelector('.tab-button[data-target=\"inventoryTab\"]');
        if (tabBtn) tabBtn.click();
        if (els.receivingProduct) {
            if (!els.receivingProduct.options.length) {
                loadReceivingProducts(productId);
            } else {
                els.receivingProduct.value = productId;
            }
        }
        if (els.receivingQty) els.receivingQty.value = 1;
    }

    async function handleAdjust(productId) {
        const deltaStr = prompt('調整庫存（使用負數表示減少）：', '0');
        if (deltaStr === null) return;
        const delta = parseInt(deltaStr, 10);
        if (!delta) {
            alert('調整數量不能為零');
            return;
        }
        const reason = prompt('調整原因：', '');
        if (reason === null || reason.trim() === '') {
            alert('調整原因為必填項目。');
            return;
        }
        try {
            const res = await api.post(`${apiUrl}?action=stock_adjust`, { product_id: productId, delta, reason: reason.trim() });
            if (res.success) {
                updateRowStock(productId, delta);
                loadMovements();
                loadDashboard();
                alert('庫存已調整。');
            } else {
                alert(res.error || '調整失敗');
            }
        } catch (err) {
            console.error('調整失敗', err);
            alert('調整失敗');
        }
    }

    function updateRowStock(productId, delta) {
        const span = document.querySelector(`.stock-value[data-id=\"${productId}\"]`);
        const hidden = document.querySelector(`input[type=\"hidden\"][data-field=\"stock\"][data-id=\"${productId}\"]`);
        if (!span || !hidden) return;
        const current = parseInt(span.textContent || '0', 10);
        const next = current + delta;
        span.textContent = next;
        hidden.value = next;
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
