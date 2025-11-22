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
