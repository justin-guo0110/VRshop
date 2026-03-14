const api = {
    get: (url) => fetch(url, { credentials: 'same-origin' }).then(r => r.json()),
    post: (url, data) => fetch(url, {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams(data)
    }).then(r => r.json())
};

document.addEventListener('DOMContentLoaded', () => {
    setupTabs();
    setupLoginPageTabs();
    bindAuth();
    bindLogout();
    bindProfile();
    bindAddresses();
    setupFilterDropdown();
    bindSearch();
    loadProductDetail();
    bindAdmin();
    bindResetPassword();
});

function setupTabs() {
    document.querySelectorAll('.tab-button').forEach(btn => {
        btn.addEventListener('click', () => {
            const target = btn.dataset.target;
            btn.parentElement.querySelectorAll('.tab-button').forEach(b => b.classList.toggle('active', b === btn));
            document.querySelectorAll('.tab-content').forEach(c => c.classList.toggle('active', c.id === target));
        });
    });
}

function setupLoginPageTabs() {
    document.querySelectorAll('.login-tab').forEach(btn => {
        btn.addEventListener('click', () => {
            const target = btn.dataset.target;
            document.querySelectorAll('.login-tab').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            document.querySelectorAll('.login-content').forEach(c => c.classList.remove('active'));
            document.getElementById(target)?.classList.add('active');
        });
    });
}

window.switchTab = function(target) {
    document.querySelectorAll('.login-tab').forEach(btn => {
        btn.classList.remove('active');
        if (btn.dataset.target === target) {
            btn.classList.add('active');
        }
    });
    document.querySelectorAll('.login-content').forEach(c => {
        c.classList.remove('active');
        if (c.id === target) {
            c.classList.add('active');
        }
    });
};

function bindAuth() {
    const loginForm = document.getElementById('loginForm');
    const registerForm = document.getElementById('registerForm');
    const message = document.getElementById('authMessage');
    const registerMsg = document.getElementById('registerMessage');
    const forgotForm = document.getElementById('forgotPasswordForm');
    const forgotMsg = document.getElementById('forgotPasswordMessage');
    if (loginForm) {
        loginForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const data = Object.fromEntries(new FormData(loginForm));
            const res = await api.post('../api/auth.php?action=login', data);
            handleAuthResponse(res, message);
        });
    }
    if (registerForm) {
        registerForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const data = Object.fromEntries(new FormData(registerForm));
            const res = await api.post('../api/auth.php?action=register', data);
            handleAuthResponse(res, registerMsg);
        });
    }
    if (forgotForm) {
        forgotForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            try {
                if (forgotMsg) {
                    forgotMsg.textContent = '寄送中，請稍候...';
                    forgotMsg.className = 'message';
                }
                const data = Object.fromEntries(new FormData(forgotForm));
                const res = await api.post('../api/auth.php?action=request_password_reset', data);
                if (forgotMsg) {
                    let msgHtml = res.message || (res.success ? '若帳號存在，將寄送重設連結。' : res.error || '送出失敗');
                    // 如果返回了重設連結（開發模式），顯示為可點擊的連結
                    if (res.reset_link) {
                        msgHtml = `${msgHtml}<br><a href="${res.reset_link}" style="color:#0066cc;text-decoration:underline;margin-top:8px;display:inline-block;">點擊此連結重設密碼</a>`;
                    }
                    forgotMsg.innerHTML = msgHtml;
                    forgotMsg.className = 'message ' + (res.success ? 'success' : 'error');
                }
                if (res.success) {
                    forgotForm.reset();
                }
            } catch (err) {
                if (forgotMsg) {
                    forgotMsg.textContent = '發生錯誤，請稍後再試';
                    forgotMsg.className = 'message error';
                }
            }
        });
    }
}

function handleAuthResponse(res, messageEl) {
    if (!messageEl) return;
    if (res.success) {
        messageEl.textContent = '登入成功，正在跳轉…';
        messageEl.className = 'message success';
        setTimeout(() => location.href = 'products.php', 500);
    } else {
        messageEl.textContent = res.error || '登入失敗';
        messageEl.className = 'message error';
    }
}

function bindLogout() {
    const btn = document.getElementById('logoutBtn');
    if (btn) {
        btn.addEventListener('click', async () => {
            await api.get('../api/auth.php?action=logout');
            location.href = 'login.php';
        });
    }
}

function bindProfile() {
    const form = document.getElementById('profileForm');
    const msg = document.getElementById('profileMessage');
    if (!form) return;
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const data = Object.fromEntries(new FormData(form));
        const res = await api.post('../api/member.php?action=update_profile', data);
        if (res.success) {
            msg.textContent = '資料已更新';
            msg.className = 'message success';
        } else {
            msg.textContent = res.error || '更新失敗';
            msg.className = 'message error';
        }
    });
}

function bindResetPassword() {
    const form = document.getElementById('resetPasswordForm');
    const message = document.getElementById('resetPasswordMessage');
    if (!form) return;
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        try {
            if (message) {
                message.textContent = '更新中...';
                message.className = 'message';
            }
            const data = Object.fromEntries(new FormData(form));
            const res = await api.post('../api/auth.php?action=reset_password', data);
            if (message) {
                message.textContent = res.message || (res.success ? '密碼已更新' : res.error || '更新失敗');
                message.className = 'message ' + (res.success ? 'success' : 'error');
            }
            if (res.success) {
                form.reset();
                setTimeout(() => location.href = 'login.php', 1200);
            }
        } catch (err) {
            if (message) {
                message.textContent = '發生錯誤，請稍後再試';
                message.className = '';
            }
        }
    });
}

let editingAddressId = null;
function bindAddresses() {
    const list = document.getElementById('addressList');
    const form = document.getElementById('addressForm');
    const msg = document.getElementById('addressMessage');
    if (!list || !form) return;

    async function loadAddresses() {
        const res = await api.get('../api/member.php?action=list_addresses');
        list.innerHTML = '';
        (res.addresses || []).forEach(addr => {
            const div = document.createElement('div');
            div.className = 'address-item';
            div.innerHTML = `
                <p><strong>${addr.recipient_name}</strong> ${addr.phone || ''}</p>
                <p>${addr.address_line}</p>
                <p></p>
                <button class="btn btn-edit" data-edit="${addr.address_id}">編輯</button>
                <button class="btn btn-danger" data-delete="${addr.address_id}">刪除</button>
            `;
            list.appendChild(div);
        });
    }
    loadAddresses();

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const data = Object.fromEntries(new FormData(form));
        let res;
        if (editingAddressId) {
            data.address_id = editingAddressId;
            res = await api.post('../api/member.php?action=update_address', data);
        } else {
            res = await api.post('../api/member.php?action=create_address', data);
        }
        if (res.success) {
            msg.textContent = '已儲存';
            msg.className = 'message success';
            form.reset();
            editingAddressId = null;
            loadAddresses();
        } else {
            msg.textContent = res.error || '儲存失敗';
            msg.className = 'message error';
        }
    });

    list.addEventListener('click', async (e) => {
        const editId = e.target.getAttribute('data-edit');
        const delId = e.target.getAttribute('data-delete');
        if (editId) {
            editingAddressId = editId;
            const res = await api.get('../api/member.php?action=list_addresses');
            const addr = (res.addresses || []).find(a => String(a.address_id) === String(editId));
            if (addr) {
                form.recipient_name.value = addr.recipient_name;
                form.phone.value = addr.phone || '';
                form.address_line.value = addr.address_line;
                form.is_default.checked = addr.is_default == 1;
            }
        }
        if (delId) {
            const res = await api.post('../api/member.php?action=delete_address', { address_id: delId });
            if (res.success) {
                loadAddresses();
            }
        }
    });
}
function setupFilterDropdown() {
    const toggle = document.getElementById('filterToggle');
    const dropdown = document.getElementById('filterDropdown');
    if (!toggle || !dropdown) return;
    
    toggle.addEventListener('click', (e) => {
        e.preventDefault();
        const isActive = dropdown.classList.toggle('active');
        toggle.classList.toggle('active', isActive);
    });
    
    // 點擊下拉選單外部時關閉
    document.addEventListener('click', (e) => {
        if (!e.target.closest('.filter-dropdown-wrapper')) {
            dropdown.classList.remove('active');
            toggle.classList.remove('active');
        }
    });
    
    // 點擊下拉選單內的元素不要關閉
    dropdown.addEventListener('click', (e) => {
        e.stopPropagation();
    });
}
async function bindSearch() {
    const form = document.getElementById('searchForm');
    const grid = document.getElementById('productGrid');
    const meta = document.getElementById('productMeta');
    const pager = document.getElementById('productPager');
    if (!form || !grid) return;

    let currentPage = 1;
    const pageSize = 12;

    function normalizeParams(raw = {}) {
        const p = { ...raw };
        Object.keys(p).forEach(k => {
            const v = p[k];
            if (v === '' || v === null || typeof v === 'undefined') delete p[k];
        });
        if (p.in_stock) p.in_stock = 1;
        p.page = currentPage;
        p.page_size = pageSize;
        return p;
    }

    async function fetchProducts(rawParams = {}) {
        const params = normalizeParams(rawParams);
        const qs = new URLSearchParams(params);
        const res = await api.get('../api/shop.php?action=search_products&' + qs.toString());
        grid.innerHTML = '';
        const products = res.products || [];
        if (!products.length) {
            grid.innerHTML = `
                <div class="card" style="grid-column:1/-1;">
                    <h3 style="margin:0 0 6px;">找不到商品</h3>
                    <p style="margin:0;color:#666;">請試著更換關鍵字、移除分類或調整價格區間。</p>
                </div>
            `;
        }
        products.forEach(p => {
            const card = document.createElement('div');
            card.className = 'product-card';
            const inStock = Number(p.stock) > 0;
            card.innerHTML = `
                <div class="media"><img src="${p.image_url || 'https://via.placeholder.com/300x200?text=Product'}" alt="${p.name}"></div>
                <h3>${p.name}</h3>
                <p class="meta">${p.category || ''}</p>
                <p class="price">$${Number(p.price).toFixed(2)}</p>
                <p style="margin:0;"><span class="stock-badge ${inStock ? 'in' : 'out'}">${inStock ? `庫存：${p.stock}` : '缺貨'}</span></p>
                <div class="card-actions">
                    <a class="btn btn-sm" href="product_detail.php?product_id=${p.product_id}">查看詳情</a>
                    <button class="btn btn-sm btn-primary add-to-cart" data-product-id="${p.product_id}" ${inStock ? '' : 'disabled'}>
                        ${inStock ? '加入購物車' : '缺貨'}
                    </button>
                </div>
            `;
            grid.appendChild(card);
        });

        const total = Number(res.total || 0);
        const totalPages = Math.max(1, Math.ceil(total / pageSize));
        if (meta) {
            meta.textContent = total ? `共 ${total} 件商品（第 ${currentPage}/${totalPages} 頁）` : '';
        }
        if (pager) {
            pager.innerHTML = '';
            if (totalPages > 1) {
                const prev = document.createElement('button');
                prev.className = 'btn btn-secondary';
                prev.textContent = '上一頁';
                prev.disabled = currentPage <= 1;
                prev.addEventListener('click', () => {
                    currentPage = Math.max(1, currentPage - 1);
                    fetchProducts(Object.fromEntries(new FormData(form)));
                });

                const next = document.createElement('button');
                next.className = 'btn btn-secondary';
                next.textContent = '下一頁';
                next.disabled = currentPage >= totalPages;
                next.addEventListener('click', () => {
                    currentPage = Math.min(totalPages, currentPage + 1);
                    fetchProducts(Object.fromEntries(new FormData(form)));
                });

                pager.appendChild(prev);
                const info = document.createElement('span');
                info.style.fontWeight = '700';
                info.textContent = `第 ${currentPage} / ${totalPages} 頁`;
                pager.appendChild(info);
                pager.appendChild(next);
            }
        }
    }

    grid.addEventListener('click', (e) => {
        const btn = e.target.closest('.add-to-cart');
        if (btn) {
            if (btn.disabled) return;
            app.addToCart(btn.dataset.productId);
        }
    });

    form.addEventListener('submit', (e) => {
        e.preventDefault();
        currentPage = 1;
        fetchProducts(Object.fromEntries(new FormData(form)));
    });

    fetchProducts();
}

async function loadProductDetail() {
    const container = document.getElementById('productDetail');
    if (!container) return;
    const params = new URLSearchParams(location.search);
    const id = params.get('product_id');
    if (!id) {
        container.innerHTML = '<p>Product not found.</p>';
        return;
    }
    const res = await api.get('../api/shop.php?action=get_product&product_id=' + encodeURIComponent(id));
    if (res.product) {
        const p = res.product;
        const inStock = Number(p.stock) > 0;
        const infoBlocks = document.getElementById('productInfoBlocks');
        container.innerHTML = `
            <div class="grid two-cols">
                <div class="media"><img src="${p.image_url || 'https://via.placeholder.com/400x260?text=Product'}" alt="${p.name}"></div>
                <div>
                    <h2>${p.name}</h2>
                    <p class="price">$${Number(p.price).toFixed(2)}</p>
                    <p style="color:#666;">${p.description || ''}</p>
                    <p style="margin:10px 0 0;"><strong>庫存：</strong> <span class="stock-badge ${inStock? 'in':'out'}" style="font-weight:800;">${inStock ? p.stock : '缺貨'}</span></p>
                    <p style="margin:6px 0 0;"><strong>分類：</strong> ${p.category || ''}</p>

                    <div style="display:flex;gap:10px;align-items:center;margin-top:14px;">
                        <label style="margin:0;font-weight:800;">數量</label>
                        <input id="detailQty" class="qty-input" type="number" min="1" ${inStock ? `max="${p.stock}"` : 'disabled'} value="1" style="width:120px;">
                    </div>
                    <div style="margin-top:12px;display:flex;gap:10px;flex-wrap:wrap;">
                        <button class="btn btn-primary add-to-cart" id="add-to-cart-btn" data-product-id="${p.product_id}" ${inStock ? '' : 'disabled'}>
                            ${inStock ? '加入購物車' : '目前缺貨'}
                        </button>
                        <a class="btn btn-secondary" href="products.php">繼續逛逛</a>
                    </div>
                </div>
            </div>
        `;
        const btn = document.getElementById('add-to-cart-btn');
        if (btn) {
            btn.addEventListener('click', () => {
                const qtyEl = document.getElementById('detailQty');
                const qty = qtyEl ? qtyEl.value : 1;
                app.addToCart(p.product_id, qty);
            });
        }
        if (infoBlocks) infoBlocks.style.display = '';
    } else {
        container.innerHTML = `<p>${res.error || 'Not found'}</p>`;
    }
}

function bindAdmin() {
    const ordersTable = document.getElementById('ordersTable');
    const productsTable = document.getElementById('productsTable');
    if (!ordersTable && !productsTable) return;

    async function loadOrders() {
        const res = await api.get('../api/admin.php?action=list_orders');
        const tbody = ordersTable.querySelector('tbody');
        tbody.innerHTML = '';
        (res.orders || []).forEach(o => {
            const row = document.createElement('tr');
            const items = (o.items || []).map(i => `${i.name} x${i.quantity}`).join('<br>');
            row.innerHTML = `
                <td>${o.order_id}</td>
                <td>${o.name || o.email}</td>
                <td><span class="badge">${o.status}</span></td>
                <td>$${Number(o.total_amount).toFixed(2)}</td>
                <td>${o.created_at}</td>
                <td>${items}</td>
                <td>
                    <select data-order="${o.order_id}">
                        ${['pending','preparing','shipping','done'].map(s => `<option value="${s}" ${s===o.status?'selected':''}>${s}</option>`).join('')}
                    </select>
                </td>
            `;
            tbody.appendChild(row);
        });
    }

    async function loadProducts() {
        const res = await api.get('../api/admin.php?action=list_products');
        const tbody = productsTable.querySelector('tbody');
        tbody.innerHTML = '';
        (res.products || []).forEach(p => {
            const row = document.createElement('tr');
            row.innerHTML = `
            <td>${p.product_id}</td>
            <td><input data-field="name" data-id="${p.product_id}" value="${p.name}"></td>
            <td><input data-field="category" data-id="${p.product_id}" value="${p.category || ''}"></td>
            <td><input data-field="price" data-id="${p.product_id}" value="${p.price}"></td>
            <td>
                <span class="stock-value" data-id="${p.product_id}">${p.stock}</span>
                <input type="hidden" data-field="stock" data-id="${p.product_id}" value="${p.stock}">
            </td>

            <!-- 上架 -->
            <td class="col-active">
                <div class="active-wrap">
                <label class="switch">
                    <input type="checkbox" data-toggle="${p.product_id}" ${p.is_active == 1 ? 'checked' : ''}>
                    <span class="slider"></span>
                </label>
                <span class="active-text">${p.is_active == 1 ? '上架' : '下架'}</span>
                </div>
            </td>

            <!-- 儲存 -->
            <td class="col-save">
                <button class="btn btn-save" data-save="${p.product_id}">儲存</button>
            </td>

            <!-- 操作 -->
            <td class="col-stock-actions">
                <div class="action-stack">
                <button class="btn btn-restock" data-restock="${p.product_id}">補貨</button>
                <button class="btn btn-adjust" data-adjust="${p.product_id}">手動調整</button>
                </div>
            </td>
            `;
            tbody.appendChild(row);
        });
    }

    if (ordersTable) {
        loadOrders();
        ordersTable.addEventListener('change', async (e) => {
            const id = e.target.getAttribute('data-order');
            if (id) {
                await api.post('../api/admin.php?action=update_order_status', { order_id: id, status: e.target.value });
            }
        });
    }

    if (productsTable) {
        loadProducts();
        productsTable.addEventListener('change', async (e) => {
            const toggleId = e.target.getAttribute('data-toggle');
            if (toggleId) {
                await api.post('../api/admin.php?action=update_product_status', { product_id: toggleId, is_active: e.target.checked ? 1 : 0 });
            }
        });
        productsTable.addEventListener('click', async (e) => {
            const saveId = e.target.getAttribute('data-save');
            if (saveId) {
                const rowInputs = productsTable.querySelectorAll(`input[data-id="${saveId}"]`);
                const payload = { product_id: saveId };
                rowInputs.forEach(inp => payload[inp.getAttribute('data-field')] = inp.value);
                await api.post('../api/admin.php?action=update_product', payload);
            }
        });
    }
}

// --- Cart & Checkout Enhancements ---
const app = window.app || {};
window.app = app;

app.addToCart = async function (product_id, quantity = 1) {
    product_id = parseInt(product_id, 10);
    if (!product_id) return;
    quantity = parseInt(quantity, 10);
    if (!quantity || quantity <= 0) quantity = 1;
    const res = await api.post('../api/cart.php?action=add', { product_id, quantity });
    if (res.success === false) {
        alert(res.error || '加入購物車失敗');
        return;
    }
    alert('已加入購物車');
    app.loadCartCount();
};

app.loadCartCount = async function () {
    const badge = document.getElementById('cart-count');
    if (!badge) return;
    try {
        const res = await api.get('../api/cart.php?action=get');
        const count = res.total_quantity || 0;
        badge.textContent = count > 0 ? `(${count})` : '';
    } catch (e) {
        badge.textContent = '';
    }
};

app.renderCartList = function (items) {
    const wrap = document.getElementById('cartItems');
    const totalEl = document.getElementById('cartTotalPrice');
    if (!wrap) return;
    wrap.innerHTML = '';
    if (!items || items.length === 0) {
        wrap.innerHTML = '<p>購物車是空的。</p>';
        if (totalEl) totalEl.textContent = '$0';
        return;
    }
    let total = 0;
    items.forEach(item => {
        const subtotal = Number(item.price) * Number(item.quantity);
        total += subtotal;
        const div = document.createElement('div');
        div.className = 'cart-item';
        div.innerHTML = `
            <div style="display:flex;gap:10px;align-items:center;">
                <img src="${item.image_url || 'https://via.placeholder.com/80'}" alt="${item.name}" style="width:80px;height:80px;object-fit:cover;border-radius:8px;">
                <div>
                    <p><strong>${item.name}</strong></p>
                    <p>單價：$${Number(item.price).toFixed(2)}</p>
                    <p>小計：$${subtotal.toFixed(2)}</p>
                </div>
            </div>
            <div style="margin-top:8px;">
                <input type="number" min="1" value="${item.quantity}" data-qty="${item.product_id}" style="width:80px;">
                <button class="btn btn-secondary update-cart" data-id="${item.product_id}">更新數量</button>
                <button class="btn btn-secondary remove-cart" data-id="${item.product_id}">刪除商品</button>
            </div>
        `;
        wrap.appendChild(div);
    });
    if (totalEl) totalEl.textContent = '$' + total.toFixed(2);
};

app.refreshCart = async function () {
    const wrap = document.getElementById('cartItems');
    if (!wrap) return;
    const res = await api.get('../api/cart.php?action=get');
    app.renderCartList(res.items || []);
    app.loadCartCount();
};

app.initCartPage = function () {
    const wrap = document.getElementById('cartItems');
    if (!wrap) return;
    app.refreshCart();
    wrap.addEventListener('click', async (e) => {
        const updateId = e.target.getAttribute('data-id');
        if (e.target.classList.contains('update-cart')) {
            const qtyInput = wrap.querySelector(`input[data-qty="${updateId}"]`);
            const qty = parseInt(qtyInput.value, 10);
            await api.post('../api/cart.php?action=update', { product_id: updateId, quantity: qty });
            app.refreshCart();
        }
        if (e.target.classList.contains('remove-cart')) {
            await api.post('../api/cart.php?action=remove', { product_id: updateId });
            app.refreshCart();
        }
    });
    const clearBtn = document.getElementById('clearCartBtn');
    if (clearBtn) {
        clearBtn.addEventListener('click', async () => {
            await api.post('../api/cart.php?action=clear', {});
            app.refreshCart();
        });
    }
};

app.loadCheckoutCart = async function () {
    const wrap = document.getElementById('checkoutCart');
    if (!wrap) return;
    const res = await api.get('../api/cart.php?action=get');
    const items = res.items || [];
    const totalsBox = document.getElementById('checkoutTotals');
    const subtotalEl = document.getElementById('checkoutSubtotal');
    if (!items.length) {
        wrap.innerHTML = '<p>購物車目前為空。</p>';
        if (totalsBox) totalsBox.style.display = 'none';
        return;
    }
    let html = '<h3 style="margin-top:0;">購物車摘要</h3><ul style="margin:0;padding-left:18px;">';
    let subtotal = 0;
    items.forEach(item => {
        const lineSubtotal = Number(item.price) * Number(item.quantity);
        subtotal += lineSubtotal;
        html += `<li>${item.name} x ${item.quantity} - $${lineSubtotal.toFixed(2)}</li>`;
    });
    html += `</ul>`;
    wrap.innerHTML = html;
    app._checkoutSubtotal = subtotal;
    if (subtotalEl) subtotalEl.textContent = '$' + subtotal.toFixed(2);
    if (totalsBox) totalsBox.style.display = '';
    app.updateCheckoutTotals();
};

app.getCheckoutFees = function (shippingMethod, paymentMethod) {
    const shippingFees = { '宅配': 100, '超商取貨': 60 };
    const paymentFees = { '信用卡': 0, '貨到付款': 30 };
    const shipping_fee = shippingFees[shippingMethod] ?? 0;
    const payment_fee = paymentFees[paymentMethod] ?? 0;
    return { shipping_fee, payment_fee };
};

app.updateCheckoutTotals = function () {
    const shippingRadio = document.querySelector('input[name="shipping_method"]:checked');
    const paymentRadio = document.querySelector('input[name="payment_method"]:checked');
    const shipping = shippingRadio ? shippingRadio.value : '';
    const payment = paymentRadio ? paymentRadio.value : '';
    const subtotal = Number(app._checkoutSubtotal || 0);

    const shippingFeeEl = document.getElementById('checkoutShippingFee');
    const paymentFeeEl = document.getElementById('checkoutPaymentFee');
    const grandEl = document.getElementById('checkoutGrandTotal');
    const etaEl = document.getElementById('checkoutEta');

    const { shipping_fee, payment_fee } = app.getCheckoutFees(shipping, payment);
    const grand = subtotal + shipping_fee + payment_fee;

    if (shippingFeeEl) shippingFeeEl.textContent = '$' + Number(shipping_fee).toFixed(2);
    if (paymentFeeEl) paymentFeeEl.textContent = '$' + Number(payment_fee).toFixed(2);
    if (grandEl) grandEl.textContent = '$' + Number(grand).toFixed(2);

    if (etaEl) {
        if (shipping === '超商取貨') {
            etaEl.textContent = '預估到貨：2-5 個工作天（依超商物流為準）';
        } else if (shipping === '宅配') {
            etaEl.textContent = '預估到貨：1-3 個工作天（偏遠地區可能較久）';
        } else {
            etaEl.textContent = '';
        }
    }
};

app.loadCheckoutAddresses = async function () {
    const wrap = document.getElementById('checkoutAddresses');
    if (!wrap) return;
    const res = await api.get('../api/member.php?action=list_addresses');
    wrap.innerHTML = '';
    const addresses = res.addresses || [];
    if (!addresses.length) {
        wrap.innerHTML = '<p>尚未建立地址，請先新增地址。</p>';
        return;
    }
    addresses.forEach((addr, idx) => {
        const label = document.createElement('label');
        label.innerHTML = `
            <input type="radio" name="address_id" value="${addr.address_id}" ${addr.is_default == 1 || idx === 0 ? 'checked' : ''}>
            ${addr.recipient_name} - ${addr.address_line} ${addr.phone || ''}
        `;
        wrap.appendChild(label);
    });
};

app.initCheckoutPage = function () {
    const placeBtn = document.getElementById('placeOrderBtn');
    if (!placeBtn) return;
    app.loadCheckoutCart();
    app.loadCheckoutAddresses();
    const msg = document.getElementById('checkoutMessage');
    let placing = false;

    const enforcePaymentRules = () => {
        const shipping = document.querySelector('input[name="shipping_method"]:checked')?.value;
        const cod = document.querySelector('input[name="payment_method"][value="貨到付款"]');
        if (cod) {
            // 常見限制：超商取貨不支援貨到付款（可依需求調整）
            const shouldDisable = shipping === '超商取貨';
            cod.disabled = shouldDisable;
            if (shouldDisable && cod.checked) {
                const cc = document.querySelector('input[name="payment_method"][value="信用卡"]');
                if (cc) cc.checked = true;
                if (msg) {
                    msg.textContent = '超商取貨目前不支援貨到付款，已自動改為信用卡。';
                    msg.className = 'message error';
                }
            }
        }
    };

    document.querySelectorAll('input[name="shipping_method"], input[name="payment_method"]').forEach(el => {
        el.addEventListener('change', () => {
            enforcePaymentRules();
            app.updateCheckoutTotals();
        });
    });
    enforcePaymentRules();

    placeBtn.addEventListener('click', async () => {
        if (placing) return;
        const msg = document.getElementById('checkoutMessage');
        const addressRadio = document.querySelector('input[name="address_id"]:checked');
        const shippingRadio = document.querySelector('input[name="shipping_method"]:checked');
        const paymentRadio = document.querySelector('input[name="payment_method"]:checked');
        if (!addressRadio || !shippingRadio || !paymentRadio) {
            if (msg) {
                msg.textContent = '請選擇地址、送貨方式與支付方式';
                msg.className = 'message error';
            }
            return;
        }
        if (msg) {
            msg.textContent = '';
            msg.className = 'message';
        }
        placing = true;
        const originalText = placeBtn.textContent;
        placeBtn.disabled = true;
        placeBtn.textContent = '下單中…';
        const payload = {
            address_id: addressRadio.value,
            shipping_method: shippingRadio.value,
            payment_method: paymentRadio.value
        };
        try {
            const res = await api.post('../api/orders.php?action=place_order', payload);
            if (res.success) {
                if (msg) {
                    msg.textContent = '下單成功！訂單編號：' + res.order_id;
                    msg.className = 'message success';
                }
                app.loadCartCount();
                setTimeout(() => location.href = 'orders.php', 800);
            } else if (msg) {
                msg.textContent = res.error || '下單失敗';
                msg.className = 'message error';
            }
        } catch (e) {
            if (msg) {
                msg.textContent = '下單失敗，請稍後再試';
                msg.className = 'message error';
            }
        } finally {
            placing = false;
            placeBtn.disabled = false;
            placeBtn.textContent = originalText;
        }
    });
};

app.statusText = function (status) {
    switch (status) {
        case 'pending': return '待處理';
        case 'preparing': return '備貨中';
        case 'shipping': return '運送中';
        case 'done': return '已完成';
        default: return status || '';
    }
};

app.loadOrders = async function () {
    const wrap = document.getElementById('ordersList');
    if (!wrap) return;
    const res = await api.get('../api/orders.php?action=list_my_orders');
    const orders = res.orders || [];
    if (!orders.length) {
        wrap.innerHTML = '<p>目前沒有訂單。</p>';
        return;
    }
    let html = '<table class="data-table"><thead><tr><th>訂單編號</th><th>時間</th><th>金額</th><th>狀態</th><th>操作</th></tr></thead><tbody>';
    orders.forEach(o => {
        html += `
            <tr data-order="${o.order_id}">
                <td>${o.order_id}</td>
                <td>${o.created_at}</td>
                <td>$${Number(o.total_price).toFixed(2)}</td>
                <td>${app.statusText(o.status)}</td>
                <td><button class="btn btn-secondary btn-sm view-order" data-id="${o.order_id}">查看明細</button></td>
            </tr>
            <tr class="order-detail" data-detail="${o.order_id}" style="display:none;">
                <td colspan="5"><div class="detail-content"></div></td>
            </tr>
        `;
    });
    html += '</tbody></table>';
    wrap.innerHTML = html;
};

app.initOrdersPage = function () {
    const wrap = document.getElementById('ordersList');
    if (!wrap) return;
    app.loadOrders();
    wrap.addEventListener('click', async (e) => {
        const btn = e.target.closest('.view-order');
        if (!btn) return;
        const orderId = btn.getAttribute('data-id');
        const detailRow = wrap.querySelector(`tr[data-detail="${orderId}"]`);
        const detailBox = detailRow ? detailRow.querySelector('.detail-content') : null;
        if (!detailRow || !detailBox) return;
        if (detailRow.style.display !== 'none') {
            detailRow.style.display = 'none';
            return;
        }
        const res = await api.get(`../api/orders.php?action=get_order_detail&order_id=${orderId}`);
        if (res.order) {
            const items = res.items || [];
            let list = `<p>狀態：${app.statusText(res.order.status)}</p>`;
            list += `<p>金額：$${Number(res.order.total_price).toFixed(2)}</p>`;
            if (res.order.payment_method) list += `<p>支付方式：${res.order.payment_method}</p>`;
            if (res.order.shipping_method) list += `<p>送貨方式：${res.order.shipping_method}</p>`;
            list += '<ul>';
            items.forEach(i => {
                list += `<li>${i.name} x ${i.quantity} - $${Number(i.price).toFixed(2)}</li>`;
            });
            list += '</ul>';
            detailBox.innerHTML = list;
            detailRow.style.display = '';
        }
    });
};

document.addEventListener('DOMContentLoaded', () => {
    app.loadCartCount();
    app.initCartPage();
    app.initCheckoutPage();
    app.initOrdersPage();
});
