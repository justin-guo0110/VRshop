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
    bindAuth();
    bindLogout();
    bindProfile();
    bindAddresses();
    bindSearch();
    loadProductDetail();
    bindAdmin();
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

function bindAuth() {
    const loginForm = document.getElementById('loginForm');
    const registerForm = document.getElementById('registerForm');
    const message = document.getElementById('authMessage');
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
            handleAuthResponse(res, message);
        });
    }
}

function handleAuthResponse(res, messageEl) {
    if (!messageEl) return;
    if (res.success) {
        messageEl.textContent = 'Success! Redirecting...';
        messageEl.className = 'message success';
        setTimeout(() => location.href = 'products.php', 500);
    } else {
        messageEl.textContent = res.error || 'Failed';
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
            msg.textContent = 'Profile updated';
            msg.className = 'message success';
        } else {
            msg.textContent = res.error || 'Update failed';
            msg.className = 'message error';
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
                <p>${addr.is_default ? '<span class="badge">Default</span>' : ''}</p>
                <button class="btn-secondary btn-sm" data-edit="${addr.address_id}">Edit</button>
                <button class="btn-secondary btn-sm" data-delete="${addr.address_id}">Delete</button>
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
            msg.textContent = 'Saved';
            msg.className = 'message success';
            form.reset();
            editingAddressId = null;
            loadAddresses();
        } else {
            msg.textContent = res.error || 'Error';
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

async function bindSearch() {
    const form = document.getElementById('searchForm');
    const grid = document.getElementById('productGrid');
    if (!form || !grid) return;

    async function fetchProducts(params = {}) {
        const qs = new URLSearchParams(params);
        const res = await api.get('../api/shop.php?action=search_products&' + qs.toString());
        grid.innerHTML = '';
        (res.products || []).forEach(p => {
            const card = document.createElement('div');
            card.className = 'product-card';
            card.innerHTML = `
                <img src="${p.image_url || 'https://via.placeholder.com/300x200?text=Product'}" alt="${p.name}">
                <h3>${p.name}</h3>
                <p class="price">$${Number(p.price).toFixed(2)}</p>
                <p>${p.category || ''}</p>
                <a class="btn" href="product_detail.php?product_id=${p.product_id}">View</a>
            `;
            grid.appendChild(card);
        });
    }

    form.addEventListener('submit', (e) => {
        e.preventDefault();
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
        container.innerHTML = `
            <div class="grid two-cols">
                <div><img style="width:100%;max-width:400px;border-radius:10px;" src="${p.image_url || 'https://via.placeholder.com/400x260?text=Product'}" alt="${p.name}"></div>
                <div>
                    <h2>${p.name}</h2>
                    <p class="price">$${Number(p.price).toFixed(2)}</p>
                    <p>${p.description || ''}</p>
                    <p><strong>Stock:</strong> ${p.stock}</p>
                    <p><strong>Category:</strong> ${p.category || ''}</p>
                </div>
            </div>
        `;
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
                <td><input data-field="stock" data-id="${p.product_id}" value="${p.stock}"></td>
                <td>
                    <label class="checkbox">
                        <input type="checkbox" data-toggle="${p.product_id}" ${p.is_active == 1 ? 'checked' : ''}> Active
                    </label>
                </td>
                <td><button class="btn-secondary btn-sm" data-save="${p.product_id}">Save</button></td>
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
    if (!items.length) {
        wrap.innerHTML = '<p>購物車目前為空。</p>';
        return;
    }
    let html = '<h3>購物車摘要</h3><ul>';
    let total = 0;
    items.forEach(item => {
        const subtotal = Number(item.price) * Number(item.quantity);
        total += subtotal;
        html += `<li>${item.name} x ${item.quantity} - $${subtotal.toFixed(2)}</li>`;
    });
    html += `</ul><p>總計：$${total.toFixed(2)}</p>`;
    wrap.innerHTML = html;
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
    placeBtn.addEventListener('click', async () => {
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
        const payload = {
            address_id: addressRadio.value,
            shipping_method: shippingRadio.value,
            payment_method: paymentRadio.value
        };
        const res = await api.post('../api/orders.php?action=place_order', payload);
        if (res.success) {
            if (msg) {
                msg.textContent = '下單成功！訂單編號：' + res.order_id;
                msg.className = 'message success';
            }
            app.loadCartCount();
            setTimeout(() => location.href = '/views/orders.php', 800);
        } else if (msg) {
            msg.textContent = res.error || '下單失敗';
            msg.className = 'message error';
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

// Override search/product detail to include add-to-cart buttons
async function bindSearch() {
    const form = document.getElementById('searchForm');
    const grid = document.getElementById('productGrid');
    if (!form || !grid) return;

    async function fetchProducts(params = {}) {
        const qs = new URLSearchParams(params);
        const res = await api.get('../api/shop.php?action=search_products&' + qs.toString());
        grid.innerHTML = '';
        (res.products || []).forEach(p => {
            const card = document.createElement('div');
            card.className = 'product-card';
            card.innerHTML = `
                <img src="${p.image_url || 'https://via.placeholder.com/300x200?text=Product'}" alt="${p.name}">
                <h3>${p.name}</h3>
                <p class="price">$${Number(p.price).toFixed(2)}</p>
                <p>${p.category || ''}</p>
                <a class="btn" href="product_detail.php?product_id=${p.product_id}">View</a>
                <button class="btn btn-sm btn-primary add-to-cart"
                        data-product-id="${p.product_id}">
                   加入購物車
                </button>
            `;
            grid.appendChild(card);
        });
    }

    grid.addEventListener('click', (e) => {
        const btn = e.target.closest('.add-to-cart');
        if (btn) {
            app.addToCart(btn.dataset.productId);
        }
    });

    form.addEventListener('submit', (e) => {
        e.preventDefault();
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
        container.innerHTML = `
            <div class="grid two-cols">
                <div><img style="width:100%;max-width:400px;border-radius:10px;" src="${p.image_url || 'https://via.placeholder.com/400x260?text=Product'}" alt="${p.name}"></div>
                <div>
                    <h2>${p.name}</h2>
                    <p class="price">$${Number(p.price).toFixed(2)}</p>
                    <p>${p.description || ''}</p>
                    <p><strong>Stock:</strong> ${p.stock}</p>
                    <p><strong>Category:</strong> ${p.category || ''}</p>
                    <button class="btn btn-primary add-to-cart" id="add-to-cart-btn" data-product-id="${p.product_id}">加入購物車</button>
                </div>
            </div>
        `;
        const btn = document.getElementById('add-to-cart-btn');
        if (btn) {
            btn.addEventListener('click', () => app.addToCart(p.product_id));
        }
    } else {
        container.innerHTML = `<p>${res.error || 'Not found'}</p>`;
    }
}

document.addEventListener('DOMContentLoaded', () => {
    app.loadCartCount();
    app.initCartPage();
    app.initCheckoutPage();
    app.initOrdersPage();
});
