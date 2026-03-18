const api = {
    get: (url) => fetch(url, { credentials: 'same-origin' }).then(r => r.json()),
    post: (url, data) => {
        const options = {
            method: 'POST',
            credentials: 'same-origin'
        };
        
        if (data instanceof FormData) {
            options.body = data;
            // Don't set Content-Type header for FormData, browser will set it automatically
        } else {
            options.headers = { 'Content-Type': 'application/x-www-form-urlencoded' };
            options.body = new URLSearchParams(data);
        }
        
        return fetch(url, options).then(r => r.json());
    }
};

// Helper function to fix image URLs based on current page location
function fixImageUrl(imageUrl) {
    if (!imageUrl) return 'https://via.placeholder.com/300x200?text=No+Image';
    // If it's already a full URL, return as-is
    if (imageUrl.startsWith('http://') || imageUrl.startsWith('https://')) return imageUrl;
    // If it's a relative path and doesn't start with /, add ../ prefix for paths from views/
    if (!imageUrl.startsWith('/')) {
        return '../' + imageUrl;
    }
    return imageUrl;
}

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
                <div class="media"><img src="${fixImageUrl(p.image_url)}" alt="${p.name}"></div>
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
                <div class="media"><img src="${fixImageUrl(p.image_url)}" alt="${p.name}"></div>
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
    // 檢查是否在管理頁面
    const adminContainer = document.querySelector('.admin-container');
    if (!adminContainer) return;

    // 加載儀表板統計
    async function loadDashboard() {
        try {
            const res = await api.get('../api/admin.php?action=list_orders');
            const orders = res.orders || [];
            
            // 計算統計數據
            const totalOrders = orders.length;
            const pendingOrders = orders.filter(o => o.status === 'pending' || o.status === 'preparing').length;
            const totalRevenue = orders.reduce((sum, o) => sum + parseFloat(o.total_amount || 0), 0);
            
            // 更新頁面上的統計卡片
            document.getElementById('statTotalOrders').textContent = totalOrders;
            document.getElementById('statPending').textContent = pendingOrders;
            document.getElementById('statTotalRevenue').textContent = '$' + totalRevenue.toFixed(2);
            
            // 載入最近訂單
            const recentOrdersTable = document.getElementById('recentOrdersTable');
            if (recentOrdersTable) {
                const tbody = recentOrdersTable.querySelector('tbody');
                tbody.innerHTML = '';
                orders.slice(0, 5).forEach(o => {
                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td>#${o.order_id}</td>
                        <td>${o.name || o.email}</td>
                        <td>$${Number(o.total_amount).toFixed(2)}</td>
                        <td><span class="badge">${o.status}</span></td>
                        <td>${o.created_at.split(' ')[0]}</td>
                    `;
                    tbody.appendChild(row);
                });
            }
        } catch (e) {
            console.error('Failed to load dashboard', e);
        }
    }

    // 加載訂單表格
    async function loadOrders() {
        try {
            const res = await api.get('../api/admin.php?action=list_orders');
            const ordersTable = document.getElementById('ordersTable');
            if (!ordersTable) return;
            
            const tbody = ordersTable.querySelector('tbody');
            tbody.innerHTML = '';
            (res.orders || []).forEach(o => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>#${o.order_id}</td>
                    <td>${o.name || o.email}</td>
                    <td><span class="badge">${o.status}</span></td>
                    <td>$${Number(o.total_amount).toFixed(2)}</td>
                    <td>${o.created_at}</td>
                    <td>
                        <select data-order="${o.order_id}">
                            ${['pending','preparing','shipping','done'].map(s => `<option value="${s}" ${s===o.status?'selected':''}>${s}</option>`).join('')}
                        </select>
                    </td>
                `;
                tbody.appendChild(row);
            });
        } catch (e) {
            console.error('Failed to load orders', e);
        }
    }

    // 加載商品表格
    async function loadProducts() {
        try {
            const res = await api.get('../api/admin.php?action=list_products');
            const productsTable = document.getElementById('productsTable');
            if (!productsTable) return;
            
            const tbody = productsTable.querySelector('tbody');
            tbody.innerHTML = '';
            (res.products || []).forEach(p => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>#${p.product_id}</td>
                    <td>
                        <div style="display:flex;gap:10px;align-items:center;">
                            <img src="${fixImageUrl(p.image_url)}" alt="${p.name}" style="width:40px;height:40px;object-fit:cover;border-radius:4px;">
                            <input data-field="name" data-id="${p.product_id}" value="${p.name}" style="flex:1;">
                        </div>
                    </td>
                    <td><input data-field="category" data-id="${p.product_id}" value="${p.category || ''}" style="width:100%;"></td>
                    <td>$<input data-field="price" data-id="${p.product_id}" value="${p.price}" type="number" step="0.01" style="width:80px;"></td>
                    <td><span class="stock-value">${p.stock}</span></td>
                    <td><label class="switch"><input type="checkbox" data-toggle="${p.product_id}" ${p.is_active == 1 ? 'checked' : ''}><span class="slider"></span></label></td>
                    <td>
                        <input data-field="image_url" data-id="${p.product_id}" value="${p.image_url || ''}" placeholder="圖片URL" style="width:130px;font-size:11px;">
                        <button class="btn btn-primary" data-save="${p.product_id}" style="margin:5px 0;width:100%;">儲存</button>
                    </td>
                `;
                tbody.appendChild(row);
            });
        } catch (e) {
            console.error('Failed to load products', e);
        }
    }

    // 頁面導航
    document.querySelectorAll('.admin-nav-item').forEach(item => {
        item.addEventListener('click', async (e) => {
            e.preventDefault();
            const page = item.getAttribute('data-page');
            
            // 更新導航狀態
            document.querySelectorAll('.admin-nav-item').forEach(i => i.classList.remove('active'));
            item.classList.add('active');
            
            // 隱藏所有頁面
            document.querySelectorAll('.admin-page').forEach(p => p.classList.remove('active'));
            
            // 顯示目標頁面
            const targetPage = document.getElementById(page + 'Page');
            if (targetPage) {
                targetPage.classList.add('active');
                
                // 根據頁面加載相應數據
                if (page === 'dashboard') loadDashboard();
                else if (page === 'orders') loadOrders();
                else if (page === 'products') loadProducts();
                else if (page === 'inventory') {
                    // 庫存頁面由 admin_inventory.js 處理
                }
            }
        });
    });

    // 側邊欄切換（手機版）
    const sidebarToggle = document.getElementById('sidebarToggle');
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', () => {
            document.querySelector('.admin-sidebar').classList.toggle('active');
        });
    }

    // 訂單表格事件監聽
    document.addEventListener('change', async (e) => {
        const orderId = e.target.getAttribute('data-order');
        if (orderId) {
            await api.post('../api/admin.php?action=update_order_status', { order_id: orderId, status: e.target.value });
        }
        
        const toggleId = e.target.getAttribute('data-toggle');
        if (toggleId) {
            await api.post('../api/admin.php?action=update_product_status', { product_id: toggleId, is_active: e.target.checked ? 1 : 0 });
        }
    });

    // 商品保存事件
    document.addEventListener('click', async (e) => {
        const saveId = e.target.getAttribute('data-save');
        if (saveId) {
            const productsTable = document.getElementById('productsTable');
            if (productsTable) {
                const rowInputs = productsTable.querySelectorAll(`input[data-id="${saveId}"]`);
                const payload = { product_id: saveId };
                rowInputs.forEach(inp => payload[inp.getAttribute('data-field')] = inp.value);
                await api.post('../api/admin.php?action=update_product', payload);
            }
        }
    });

    // 新增商品表單
    const newProductBtn = document.getElementById('newProductBtn');
    const newProductForm = document.getElementById('newProductForm');
    const cancelProductBtn = document.getElementById('cancelProductBtn');
    const createProductBtn = document.getElementById('createProductBtn');
    
    if (newProductBtn) {
        newProductBtn.addEventListener('click', () => {
            newProductForm.style.display = newProductForm.style.display === 'none' ? 'block' : 'none';
        });
    }
    
    if (cancelProductBtn) {
        cancelProductBtn.addEventListener('click', () => {
            newProductForm.style.display = 'none';
            document.getElementById('newProductName').value = '';
            document.getElementById('newProductCategory').value = '';
            document.getElementById('newProductPrice').value = '';
            document.getElementById('newProductStock').value = '0';
            document.getElementById('newProductDescription').value = '';
            document.getElementById('newProductImageUrl').value = '';
        });
    }
    
    if (createProductBtn) {
        createProductBtn.addEventListener('click', async () => {
            const formData = new FormData();
            formData.append('name', document.getElementById('newProductName').value);
            formData.append('category', document.getElementById('newProductCategory').value);
            formData.append('price', document.getElementById('newProductPrice').value);
            formData.append('stock', document.getElementById('newProductStock').value);
            formData.append('description', document.getElementById('newProductDescription').value);
            formData.append('image_url', document.getElementById('newProductImageUrl').value);
            
            try {
                const res = await api.post('../api/admin.php?action=create_product', formData);
                if (res.success) {
                    alert('商品新增成功！');
                    newProductForm.style.display = 'none';
                    document.getElementById('newProductName').value = '';
                    document.getElementById('newProductCategory').value = '';
                    document.getElementById('newProductPrice').value = '';
                    document.getElementById('newProductStock').value = '0';
                    document.getElementById('newProductDescription').value = '';
                    document.getElementById('newProductImageUrl').value = '';
                    await loadProducts();
                } else {
                    alert('新增失敗：' + (res.error || '未知錯誤'));
                }
            } catch (err) {
                alert('新增失敗：' + err);
            }
        });
    }

    // 登出按鈕
    const logoutBtn = document.getElementById('logoutBtn');
    if (logoutBtn) {
        logoutBtn.addEventListener('click', () => {
            if (confirm('確定要登出嗎？')) {
                window.location.href = '../views/index.php?logout=1';
            }
        });
    }

    // 初始加載儀表板
    loadDashboard();
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

app.renderCartList = function (items, selectedIds = []) {
    const wrap = document.getElementById('cartItems');
    const totalEl = document.getElementById('cartTotalPrice');
    if (!wrap) return;
    wrap.innerHTML = '';
    const selectedSet = new Set((selectedIds || []).map(id => String(id)));
    const useSelection = selectedSet.size > 0;
    if (!items || items.length === 0) {
        wrap.innerHTML = '<p>購物車是空的。</p>';
        if (totalEl) totalEl.textContent = '$0';
        app.updateCartSelectionSummary();
        return;
    }
    let total = 0;
    items.forEach(item => {
        const subtotal = Number(item.price) * Number(item.quantity);
        total += subtotal;
        const checked = useSelection ? selectedSet.has(String(item.product_id)) : true;
        const div = document.createElement('div');
        div.className = 'cart-item';
        div.innerHTML = `
            <div style="display:flex;gap:10px;align-items:center;">
                <input
                    type="checkbox"
                    class="cart-item-checkbox"
                    data-product-id="${item.product_id}"
                    data-subtotal="${subtotal.toFixed(2)}"
                    ${checked ? 'checked' : ''}
                    style="width:18px;height:18px;cursor:pointer;"
                >
                <img src="${fixImageUrl(item.image_url)}" alt="${item.name}" style="width:80px;height:80px;object-fit:cover;border-radius:8px;">
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
    app.updateCartSelectionSummary();
};

app.getSelectedCartProductIds = function () {
    return Array.from(document.querySelectorAll('.cart-item-checkbox:checked')).map(el => String(el.dataset.productId));
};

app.updateCartSelectionSummary = function () {
    const selectedPriceEl = document.getElementById('cartSelectedPrice');
    const selectedCountEl = document.getElementById('selectedCount');
    const checkoutBtn = document.getElementById('checkoutSelectedBtn');
    const checked = Array.from(document.querySelectorAll('.cart-item-checkbox:checked'));
    const selectedCount = checked.length;
    const selectedTotal = checked.reduce((sum, el) => sum + Number(el.dataset.subtotal || 0), 0);

    if (selectedPriceEl) selectedPriceEl.textContent = '$' + selectedTotal.toFixed(2);
    if (selectedCountEl) selectedCountEl.textContent = String(selectedCount);

    if (checkoutBtn) {
        checkoutBtn.disabled = selectedCount === 0;
        checkoutBtn.textContent = selectedCount > 0 ? `前往結帳（${selectedCount} 件）` : '前往結帳（請先勾選商品）';
    }
};

app.refreshCart = async function (preserveSelection = false) {
    const wrap = document.getElementById('cartItems');
    if (!wrap) return;
    const selectedIds = preserveSelection ? app.getSelectedCartProductIds() : [];
    const res = await api.get('../api/cart.php?action=get');
    app.renderCartList(res.items || [], selectedIds);
    app.loadCartCount();
};

app.initCartPage = function () {
    const wrap = document.getElementById('cartItems');
    if (!wrap) return;
    app.refreshCart();
    wrap.addEventListener('change', (e) => {
        if (e.target.classList.contains('cart-item-checkbox')) {
            app.updateCartSelectionSummary();
        }
    });
    wrap.addEventListener('click', async (e) => {
        const updateId = e.target.getAttribute('data-id');
        if (e.target.classList.contains('update-cart')) {
            const qtyInput = wrap.querySelector(`input[data-qty="${updateId}"]`);
            const qty = parseInt(qtyInput.value, 10);
            await api.post('../api/cart.php?action=update', { product_id: updateId, quantity: qty });
            app.refreshCart(true);
        }
        if (e.target.classList.contains('remove-cart')) {
            await api.post('../api/cart.php?action=remove', { product_id: updateId });
            app.refreshCart(true);
        }
    });

    const checkoutBtn = document.getElementById('checkoutSelectedBtn');
    if (checkoutBtn) {
        checkoutBtn.addEventListener('click', () => {
            const selectedIds = app.getSelectedCartProductIds();
            if (!selectedIds.length) {
                alert('請至少勾選一個商品再結帳');
                return;
            }
            const selected = encodeURIComponent(selectedIds.join(','));
            location.href = `./checkout.php?selected=${selected}`;
        });
    }

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
    const cartItems = res.items || [];
    const selectedParam = new URLSearchParams(location.search).get('selected') || '';
    const selectedIds = selectedParam
        .split(',')
        .map(v => parseInt(v, 10))
        .filter(v => Number.isInteger(v) && v > 0);
    const selectedSet = new Set(selectedIds.map(String));
    const items = selectedSet.size > 0
        ? cartItems.filter(item => selectedSet.has(String(item.product_id)))
        : cartItems;
    const totalsBox = document.getElementById('checkoutTotals');
    const subtotalEl = document.getElementById('checkoutSubtotal');
    if (!items.length) {
        wrap.innerHTML = '<p style="text-align: center; color: var(--muted); padding: 20px;">沒有可結帳商品，請先回購物車勾選商品。</p>';
        if (totalsBox) totalsBox.style.display = 'none';
        return;
    }
    
    let html = '';
    items.forEach((item) => {
        const lineSubtotal = Number(item.price) * Number(item.quantity);
        const itemId = `checkout-item-${item.product_id}`;
        html += `
            <div class="checkout-item" data-product-id="${item.product_id}">
                <input type="checkbox" class="item-checkbox" data-product-id="${item.product_id}" checked>
                
                <div class="checkout-item-info">
                    <div class="checkout-item-name">${item.name}</div>
                    <div class="checkout-item-price">$${Number(item.price).toFixed(2)}/件</div>
                </div>
                
                <div class="checkout-item-controls">
                    <div class="quantity-control">
                        <button class="qty-btn-minus" data-product-id="${item.product_id}">−</button>
                        <input type="number" class="qty-input" data-product-id="${item.product_id}" value="${item.quantity}" min="1" max="999">
                        <button class="qty-btn-plus" data-product-id="${item.product_id}">+</button>
                    </div>
                    
                    <div class="checkout-item-subtotal">$${lineSubtotal.toFixed(2)}</div>
                    
                    <button class="btn-delete" data-product-id="${item.product_id}">🗑️ 刪除</button>
                </div>
            </div>
        `;
    });
    
    wrap.innerHTML = html;
    
    // 設置購物車數據
    window.checkoutCartItems = {};
    items.forEach(item => {
        window.checkoutCartItems[item.product_id] = {
            ...item,
            checked: true,
            price: Number(item.price),
            quantity: Number(item.quantity)
        };
    });
    
    // 綁定複選框事件
    document.querySelectorAll('.item-checkbox').forEach(checkbox => {
        checkbox.addEventListener('change', (e) => {
            const productId = e.target.dataset.productId;
            const item = window.checkoutCartItems[productId];
            if (item) {
                item.checked = e.target.checked;
                e.target.closest('.checkout-item').classList.toggle('unchecked', !e.target.checked);
                app.updateCheckoutTotals();
            }
        });
    });
    
    // 綁定數量調整事件
    document.querySelectorAll('.qty-input').forEach(input => {
        input.addEventListener('change', (e) => {
            const productId = e.target.dataset.productId;
            const newQty = Math.max(1, parseInt(e.target.value) || 1);
            const item = window.checkoutCartItems[productId];
            if (item) {
                item.quantity = newQty;
                e.target.value = newQty;
                
                // 更新小計和樣式
                const itemDiv = e.target.closest('.checkout-item');
                const subtotal = item.price * newQty;
                itemDiv.querySelector('.checkout-item-subtotal').textContent = '$' + subtotal.toFixed(2);
                app.updateCheckoutTotals();
            }
        });
    });
    
    // 綁定加/減按鈕事件
    document.querySelectorAll('.qty-btn-plus').forEach(btn => {
        btn.addEventListener('click', (e) => {
            const productId = e.target.dataset.productId;
            const input = document.querySelector(`.qty-input[data-product-id="${productId}"]`);
            if (input) {
                input.value = Math.max(1, parseInt(input.value) || 1) + 1;
                input.dispatchEvent(new Event('change', { bubbles: true }));
            }
        });
    });
    
    document.querySelectorAll('.qty-btn-minus').forEach(btn => {
        btn.addEventListener('click', (e) => {
            const productId = e.target.dataset.productId;
            const input = document.querySelector(`.qty-input[data-product-id="${productId}"]`);
            if (input) {
                input.value = Math.max(1, (parseInt(input.value) || 1) - 1);
                input.dispatchEvent(new Event('change', { bubbles: true }));
            }
        });
    });
    
    // 綁定刪除按鈕事件
    document.querySelectorAll('.btn-delete').forEach(btn => {
        btn.addEventListener('click', async (e) => {
            const productId = e.target.dataset.productId;
            if (confirm('確定要刪除此商品嗎？')) {
                const res = await api.post('../api/cart.php?action=remove', { product_id: productId });
                if (res.success) {
                    delete window.checkoutCartItems[productId];
                    app.loadCheckoutCart();
                    app.loadCartCount();
                }
            }
        });
    });
    
    if (subtotalEl) subtotalEl.textContent = '$0'; // 將在 updateCheckoutTotals 更新
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
    
    // 只計算勾選的商品小計
    let subtotal = 0;
    if (window.checkoutCartItems) {
        Object.values(window.checkoutCartItems).forEach(item => {
            if (item.checked) {
                subtotal += item.price * item.quantity;
            }
        });
    }

    const shippingFeeEl = document.getElementById('checkoutShippingFee');
    const paymentFeeEl = document.getElementById('checkoutPaymentFee');
    const grandEl = document.getElementById('checkoutGrandTotal');
    const subtotalEl = document.getElementById('checkoutSubtotal');
    const etaEl = document.getElementById('checkoutEta');

    const { shipping_fee, payment_fee } = app.getCheckoutFees(shipping, payment);
    const grand = subtotal + shipping_fee + payment_fee;

    if (subtotalEl) subtotalEl.textContent = '$' + Number(subtotal).toFixed(2);
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
        wrap.innerHTML = '<p style="text-align: center; color: var(--muted);">尚未建立地址，請先新增地址。</p>';
        return;
    }
    addresses.forEach((addr, idx) => {
        const isChecked = addr.is_default == 1 || idx === 0;
        const div = document.createElement('div');
        div.className = 'address-item' + (isChecked ? ' selected' : '');
        div.innerHTML = `
            <input type="radio" name="address_id" value="${addr.address_id}" ${isChecked ? 'checked' : ''}>
            <div class="address-item-content">
                <div class="address-item-name">${addr.recipient_name}</div>
                <div class="address-item-info">
                    📍 ${addr.address_line}<br>
                    ☎️ ${addr.phone || '未提供電話'}
                </div>
            </div>
        `;
        div.addEventListener('click', (e) => {
            if (e.target.tagName !== 'INPUT') {
                const radio = div.querySelector('input[type="radio"]');
                radio.checked = true;
                radio.dispatchEvent(new Event('change', { bubbles: true }));
            }
        });
        wrap.appendChild(div);
    });

    // 監聽地址選擇變化
    document.querySelectorAll('#checkoutAddresses input[name="address_id"]').forEach(el => {
        el.addEventListener('change', () => {
            document.querySelectorAll('.address-item').forEach(item => item.classList.remove('selected'));
            el.closest('.address-item')?.classList.add('selected');
        });
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

    // 為所有廣播按鈕添加事件監聽
    document.querySelectorAll('input[name="shipping_method"], input[name="payment_method"]').forEach(el => {
        el.addEventListener('change', () => {
            // 更新選項項目的視覺効果
            const container = el.closest('.options-group');
            if (container) {
                container.querySelectorAll('.option-item').forEach(item => {
                    const radio = item.querySelector('input[type="radio"]');
                    if (radio?.checked) {
                        item.classList.add('selected');
                    } else {
                        item.classList.remove('selected');
                    }
                });
            }
            enforcePaymentRules();
            app.updateCheckoutTotals();
        });
    });

    // 為所有選項項目添加點擊事件
    document.querySelectorAll('.option-item').forEach(item => {
        item.addEventListener('click', (e) => {
            if (e.target.tagName !== 'INPUT') {
                const radio = item.querySelector('input[type="radio"]');
                if (radio) {
                    radio.checked = true;
                    radio.dispatchEvent(new Event('change', { bubbles: true }));
                }
            }
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

        // 檢查是否至少選擇了一個商品
        const checkedItems = Object.values(window.checkoutCartItems || {}).filter(item => item.checked);
        if (!checkedItems.length) {
            if (msg) {
                msg.textContent = '請至少選擇一個商品進行結帳';
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

        try {
            // 將已勾選商品清單送到後端，只建立這些商品的訂單
            const payload = {
                address_id: addressRadio.value,
                shipping_method: shippingRadio.value,
                payment_method: paymentRadio.value,
                selected_product_ids: checkedItems.map(item => item.product_id).join(',')
            };
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
