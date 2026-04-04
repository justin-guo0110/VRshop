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

const DEFAULT_PRODUCT_IMAGE = 'https://via.placeholder.com/300x200?text=No+Image';

// Keep the database image_url as-is; only fall back when it's empty.
function fixImageUrl(imageUrl) {
    if (!imageUrl) return DEFAULT_PRODUCT_IMAGE;
    const normalized = String(imageUrl).trim().replace(/\\/g, '/');
    // Route local image paths through a PHP proxy to handle Chinese filenames
    // and spaces that Apache on Windows cannot serve directly via URL.
    const match = normalized.match(/(?:\.\.\/)*image\/(.+)$/);
    if (match) {
        return '../api/image.php?path=' + encodeURIComponent(match[1]);
    }
    return normalized;
}

function getImageFallbackAttr() {
    return `this.onerror=null;this.src='${DEFAULT_PRODUCT_IMAGE}';`;
}

document.addEventListener('DOMContentLoaded', () => {
    setupTabs();
    setupLoginPageTabs();
    bindAuth();
    bindLogout();
    bindHeaderCouponDropdown();
    bindUserWelcomeDropdown();
    bindProfile();
    bindAddresses();
    bindSearch();
    loadProductDetail();
    bindAdmin();
    bindResetPassword();
});

function bindUserWelcomeDropdown() {
    const wrapper = document.getElementById('userWelcomeDropdown');
    const toggle = document.getElementById('userWelcomeToggle');
    const menu = document.getElementById('userWelcomeMenu');
    if (!wrapper || !toggle || !menu) return;

    toggle.addEventListener('click', (e) => {
        e.preventDefault();
        e.stopPropagation();
        const isOpen = wrapper.classList.toggle('open');
        toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    });

    menu.addEventListener('click', (e) => {
        e.stopPropagation();
    });

    document.addEventListener('click', () => {
        wrapper.classList.remove('open');
        toggle.setAttribute('aria-expanded', 'false');
    });
}

async function bindHeaderCouponDropdown() {
    const list = document.getElementById('headerCouponList');
    if (!list) return;

    try {
        const res = await api.get('../api/lucky_wheel.php?action=list_coupons&only_active=1');
        const coupons = (res.coupons || []).slice(0, 5);

        if (!coupons.length) {
            list.innerHTML = '<p class="coupon-dropdown-empty">目前沒有可用優惠券</p>';
            return;
        }

        const items = coupons.map((c) => {
            const desc = app.escapeHtml(c.description || '優惠券');
            const code = app.escapeHtml(c.coupon_code || '');
            const discount = c.discount_type === 'percent'
                ? `${Number(c.discount_value)}%`
                : `$${Number(c.discount_value).toFixed(0)}`;

            return `
                <div class="coupon-dropdown-item">
                    <div class="coupon-dropdown-item-top">
                        <span class="coupon-dropdown-name">${desc}</span>
                        <strong class="coupon-dropdown-discount">${discount}</strong>
                    </div>
                    <div class="coupon-dropdown-code">${code}</div>
                </div>
            `;
        }).join('');

        list.innerHTML = `${items}<a class="coupon-dropdown-more" href="../views/coupons.php">查看全部優惠券</a>`;
    } catch (error) {
        list.innerHTML = '<p class="coupon-dropdown-error">優惠券載入失敗</p>';
    }
}

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
        messageEl.textContent = res.message || '登入成功，正在跳轉…';
        messageEl.className = 'message success';
        const role = res?.user?.role || '';
        if (role === 'admin') {
            sessionStorage.removeItem('showLuckyWheelOnLogin');
            setTimeout(() => location.href = 'admin.php?page=dashboard', 500);
            return;
        }

        sessionStorage.setItem('showLuckyWheelOnLogin', '1');
        setTimeout(() => location.href = 'products.php', 500);
    } else {
        messageEl.textContent = res.error || '登入失敗';
        messageEl.className = 'message error';
    }
}

async function maybeShowLuckyWheelAfterLogin() {
    const shouldShow = sessionStorage.getItem('showLuckyWheelOnLogin') === '1';
    if (!shouldShow) return;

    const path = (location.pathname || '').toLowerCase();
    if (path.endsWith('/login.php') || path.endsWith('/admin.php')) {
        sessionStorage.removeItem('showLuckyWheelOnLogin');
        return;
    }

    try {
        const me = await api.get('../api/auth.php?action=me');
        const role = me?.user?.role || '';
        if (role === 'admin') {
            sessionStorage.removeItem('showLuckyWheelOnLogin');
            return;
        }
    } catch (err) {
        sessionStorage.removeItem('showLuckyWheelOnLogin');
        return;
    }

    sessionStorage.removeItem('showLuckyWheelOnLogin');

    const openModal = () => {
        if (typeof openLuckyWheel === 'function') {
            setTimeout(() => openLuckyWheel(), 300);
        }
    };

    if (typeof openLuckyWheel === 'function') {
        openModal();
        return;
    }

    const script = document.createElement('script');
    script.src = '../public/js/lucky_wheel.js?v=' + Date.now();
    script.onload = openModal;
    script.onerror = () => console.error('無法載入 lucky_wheel.js');
    document.head.appendChild(script);
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
                <div class="address-item-content">
                    <div class="address-item-name">${addr.recipient_name}</div>
                    <div class="address-item-info">
                        ${addr.phone ? `<div>${addr.phone}</div>` : ''}
                        <div>${addr.address_line}</div>
                    </div>
                </div>
                <div class="address-item-actions">
                    <button type="button" class="btn btn-edit" data-edit="${addr.address_id}">編輯</button>
                    <button type="button" class="btn btn-danger" data-delete="${addr.address_id}">刪除</button>
                </div>
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
            const imageUrl = fixImageUrl(p.image_url);
            card.innerHTML = `
                <div class="media"><img src="${imageUrl}" alt="${p.name}" onerror="${getImageFallbackAttr()}"></div>
                <h3>${p.name}</h3>
                <p class="meta">${p.category || '未分類'}</p>
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

    // 分類按鈕快速篩選
    const categoryBtns = document.querySelectorAll('.category-filter-btn');
    categoryBtns.forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            
            // 更新按鈕狀態
            categoryBtns.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            
            // 更新表單分類值
            const categoryField = form.querySelector('[name="category"]');
            if (categoryField) {
                categoryField.value = btn.dataset.category || '';
            }
            
            // 重新搜尋
            currentPage = 1;
            fetchProducts(Object.fromEntries(new FormData(form)));
        });
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
        const imageUrl = fixImageUrl(p.image_url);
        container.innerHTML = `
            <div class="grid two-cols">
                <div class="media"><img src="${imageUrl}" alt="${p.name}" onerror="${getImageFallbackAttr()}"></div>
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
            const pendingOrders = orders.filter(o => ['accepted', 'pending', 'preparing'].includes(o.status)).length;
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
    async function loadOrders(filterStatus = 'all', searchKeyword = '') {
        try {
            const res = await api.get('../api/admin.php?action=list_orders');
            const ordersTable = document.getElementById('ordersTable');
            if (!ordersTable) return;
            
            let orders = res.orders || [];
            
            // 按狀態篩選
            if (filterStatus !== 'all') {
                orders = orders.filter(o => o.status === filterStatus);
            }
            
            // 按關鍵字搜尋
            if (searchKeyword) {
                const keyword = searchKeyword.toLowerCase();
                orders = orders.filter(o => 
                    String(o.order_id).includes(keyword) ||
                    (o.name || '').toLowerCase().includes(keyword) ||
                    (o.email || '').toLowerCase().includes(keyword)
                );
            }
            
            const tbody = ordersTable.querySelector('tbody');
            tbody.innerHTML = '';
            
            if (orders.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;color:#999;">沒有找到符合的訂單</td></tr>';
                return;
            }
            
            orders.forEach(o => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>#${o.order_id}</td>
                    <td>${o.name || o.email}</td>
                    <td><span class="badge">${o.status}</span></td>
                    <td>$${Number(o.total_amount).toFixed(2)}</td>
                    <td>${o.created_at}</td>
                    <td>
                        <select data-order="${o.order_id}" style="margin-right:5px;">
                            ${['accepted','preparing','shipping','done','cancelled'].map(s => `<option value="${s}" ${s===o.status?'selected':''}>${app.statusText(s)}</option>`).join('')}
                        </select>
                        <button class="btn btn-sm btn-danger delete-order" data-id="${o.order_id}" style="padding:4px 8px;">刪除</button>
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
                            <img src="${p.image_url}" alt="${p.name}" style="width:40px;height:40px;object-fit:cover;border-radius:4px;">
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
                        <button class="btn btn-danger" data-delete="${p.product_id}" style="margin:5px 0;width:100%;padding:6px 12px;font-size:12px;">刪除</button>
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

    // 訂單過滤按鈕
    document.querySelectorAll('.order-filter-btn').forEach(btn => {
        btn.addEventListener('click', async () => {
            const status = btn.getAttribute('data-status');
            const searchInput = document.querySelector('.admin-toolbar input[type="text"]');
            const searchKeyword = searchInput ? searchInput.value : '';
            
            document.querySelectorAll('.order-filter-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            
            await loadOrders(status, searchKeyword);
        });
    });
    
    // 訂單搜尋
    const orderSearchInput = document.querySelector('.admin-toolbar input[type="text"]');
    if (orderSearchInput) {
        orderSearchInput.disabled = false;
        orderSearchInput.placeholder = '搜尋訂單編號或顧客';
        orderSearchInput.addEventListener('input', async (e) => {
            const activeBtn = document.querySelector('.order-filter-btn.active');
            const status = activeBtn ? activeBtn.getAttribute('data-status') : 'all';
            await loadOrders(status, e.target.value);
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

    // 訂單刪除和商品刪除事件
    document.addEventListener('click', async (e) => {
        // 商品保存
        const saveId = e.target.getAttribute('data-save');
        if (saveId) {
            const productsTable = document.getElementById('productsTable');
            if (productsTable) {
                const rowInputs = productsTable.querySelectorAll(`input[data-id="${saveId}"]`);
                const payload = { product_id: saveId };
                rowInputs.forEach(inp => payload[inp.getAttribute('data-field')] = inp.value);
                const res = await api.post('../api/admin.php?action=update_product', payload);
                if (res.success) {
                    alert('商品已儲存');
                    await loadProducts();
                } else {
                    alert('儲存失敗：' + (res.error || '未知錯誤'));
                }
            }
        }
        
        // 商品刪除
        const deleteId = e.target.getAttribute('data-delete');
        if (deleteId) {
            if (confirm('確定要刪除此商品嗎？此操作無法復原。')) {
                try {
                    const res = await api.post('../api/admin.php?action=delete_product', { product_id: deleteId });
                    if (res.success) {
                        alert('商品已刪除');
                        await loadProducts();
                    } else {
                        alert('刪除失敗：' + (res.error || '未知錯誤'));
                    }
                } catch (err) {
                    alert('刪除失敗：' + err);
                }
            }
        }
        
        // 訂單刪除
        const deleteOrderId = e.target.getAttribute('data-id');
        if (e.target.classList.contains('delete-order') && deleteOrderId) {
            if (confirm('確定要刪除此訂單嗎？此操作無法復原。')) {
                try {
                    const res = await api.post('../api/admin.php?action=delete_order', { order_id: deleteOrderId });
                    if (res.success) {
                        alert('訂單已刪除');
                        const activeBtn = document.querySelector('.order-filter-btn.active');
                        const status = activeBtn ? activeBtn.getAttribute('data-status') : 'all';
                        await loadOrders(status);
                    } else {
                        alert('刪除失敗：' + (res.error || '未知錯誤'));
                    }
                } catch (err) {
                    alert('刪除失敗：' + err);
                }
            }
        }
    });

    // 商品保存事件（保留舊的監聽方式）
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

app.toast = function (message, type = 'info', duration = 2200) {
    if (!message) return;
    if (!document.getElementById('appToastStyle')) {
        const style = document.createElement('style');
        style.id = 'appToastStyle';
        style.textContent = `
            .app-toast-wrap { position: fixed; right: 16px; bottom: 16px; z-index: 2200; display: flex; flex-direction: column; gap: 8px; pointer-events: none; }
            .app-toast { min-width: 220px; max-width: 360px; padding: 10px 12px; border-radius: 10px; color: #fff; font-size: 0.9rem; box-shadow: 0 10px 22px rgba(15, 23, 42, 0.24); transform: translateY(8px); opacity: 0; transition: all 0.2s ease; }
            .app-toast.show { transform: translateY(0); opacity: 1; }
            .app-toast.info { background: #334155; }
            .app-toast.success { background: #0f766e; }
            .app-toast.warning { background: #b45309; }
            .app-toast.error { background: #b91c1c; }
        `;
        document.head.appendChild(style);
    }

    let wrap = document.getElementById('appToastWrap');
    if (!wrap) {
        wrap = document.createElement('div');
        wrap.id = 'appToastWrap';
        wrap.className = 'app-toast-wrap';
        document.body.appendChild(wrap);
    }

    const toast = document.createElement('div');
    toast.className = `app-toast ${type}`;
    toast.textContent = String(message);
    wrap.appendChild(toast);

    requestAnimationFrame(() => toast.classList.add('show'));
    const removeToast = () => {
        toast.classList.remove('show');
        setTimeout(() => {
            toast.remove();
        }, 180);
    };
    setTimeout(removeToast, Math.max(1000, Number(duration) || 2200));
};

app.addToCart = async function (product_id, quantity = 1) {
    product_id = parseInt(product_id, 10);
    if (!product_id) return;
    quantity = parseInt(quantity, 10);
    if (!quantity || quantity <= 0) quantity = 1;
    const res = await api.post('../api/cart.php?action=add', { product_id, quantity });
    if (res.success === false) {
        app.toast(res.error || '加入購物車失敗', 'error');
        return;
    }
    app.toast('已加入購物車', 'success');
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
                <img src="${item.image_url}" alt="${item.name}" style="width:80px;height:80px;object-fit:cover;border-radius:8px;">
                <div style="padding-left:30px">
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
            if (!Number.isInteger(qty) || qty <= 0) {
                app.toast('數量至少要 1', 'warning');
                return;
            }
            const updateRes = await api.post('../api/cart.php?action=update', { product_id: updateId, quantity: qty });
            if (updateRes.success === false) {
                app.toast(updateRes.error || '更新數量失敗', 'error');
                return;
            }
            app.toast(`數量已更新為 ${qty}`, 'success', 1500);
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
        input.addEventListener('change', async (e) => {
            const productId = e.target.dataset.productId;
            const newQty = Math.max(1, parseInt(e.target.value) || 1);
            const item = window.checkoutCartItems[productId];
            if (item) {
                item.quantity = newQty;
                e.target.value = newQty;

                try {
                    const updateRes = await api.post('../api/cart.php?action=update', {
                        product_id: productId,
                        quantity: newQty
                    });
                    if (updateRes.success === false) {
                        app.toast(updateRes.error || '更新數量失敗', 'error');
                        await app.loadCheckoutCart();
                        return;
                    }
                } catch (err) {
                    // 若更新失敗，重新載入以與後端狀態一致
                    app.toast('更新數量失敗，已重新整理購物資料', 'error');
                    await app.loadCheckoutCart();
                    return;
                }
                
                // 更新小計和樣式
                const itemDiv = e.target.closest('.checkout-item');
                const subtotal = item.price * newQty;
                itemDiv.querySelector('.checkout-item-subtotal').textContent = '$' + subtotal.toFixed(2);
                app.updateCheckoutTotals();
                app.toast(`已更新 ${(item.name || '商品')} 數量為 ${newQty}`, 'success', 1500);
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
    app.loadCheckoutCoupons();
};

app.FREE_SHIPPING = { '宅配': 499, '超商取貨': 299 };
app.BASE_SHIPPING_FEES = { '宅配': 100, '超商取貨': 60 };
app.BUNDLE_PROMO_GROUPS = {
    beverage: ['咖啡', '奶類', '巧克力', '果汁', '碳酸飲料', '茶類', '運動飲料'],
    snack: ['糖果', '膨化零食', '餅乾']
};

app.getCheckoutFees = function (shippingMethod, paymentMethod, subtotal) {
    const paymentFees = { '信用卡': 0, '貨到付款': 30 };
    const baseFee = app.BASE_SHIPPING_FEES[shippingMethod] ?? 0;
    const threshold = app.FREE_SHIPPING[shippingMethod] ?? null;
    const freeShipping = threshold !== null && subtotal >= threshold;
    const shipping_fee = freeShipping ? 0 : baseFee;
    const payment_fee = paymentFees[paymentMethod] ?? 0;
    return { shipping_fee, payment_fee, freeShipping, threshold, baseFee };
};

app.checkoutCoupon = null;
app.checkoutCouponsCache = [];

app.getCheckoutSubtotal = function () {
    let subtotal = 0;
    if (window.checkoutCartItems) {
        Object.values(window.checkoutCartItems).forEach(item => {
            if (item.checked) subtotal += item.price * item.quantity;
        });
    }
    return subtotal;
};

app.getCheckoutBundlePromotions = function () {
    const summary = {
        discount: 0,
        details: []
    };
    if (!window.checkoutCartItems) return summary;

    let beverageQty = 0;
    let beverageSubtotal = 0;
    let snackQty = 0;
    let snackSubtotal = 0;

    Object.values(window.checkoutCartItems).forEach(item => {
        if (!item.checked) return;
        const category = String(item.category || '').trim();
        const quantity = Number(item.quantity || 0);
        const lineSubtotal = Number(item.price || 0) * quantity;

        if (app.BUNDLE_PROMO_GROUPS.beverage.includes(category)) {
            beverageQty += quantity;
            beverageSubtotal += lineSubtotal;
        }
        if (app.BUNDLE_PROMO_GROUPS.snack.includes(category)) {
            snackQty += quantity;
            snackSubtotal += lineSubtotal;
        }
    });

    if (beverageQty >= 2 && beverageSubtotal > 0) {
        const discount = Number((beverageSubtotal * 0.12).toFixed(2));
        summary.discount += discount;
        summary.details.push({
            code: 'beverage_2_88',
            label: '飲料任選 2 件 88 折',
            discount
        });
    }

    if (snackQty >= 3 && snackSubtotal > 0) {
        const discount = Math.min(Math.floor(snackQty / 3) * 20, snackSubtotal);
        summary.discount += discount;
        summary.details.push({
            code: 'snack_3_minus_20',
            label: '零食任選 3 件折 $20',
            discount: Number(discount.toFixed(2))
        });
    }

    summary.discount = Number(summary.discount.toFixed(2));
    return summary;
};

app.renderCheckoutPromotionTips = function (promotionInfo) {
    const list = document.getElementById('checkoutPromotionTips');
    if (!list) return;

    const activeDetails = promotionInfo?.details || [];
    if (activeDetails.length) {
        list.innerHTML = activeDetails.map(detail => `
            <div class="checkout-promo-item active">已套用 ${app.escapeHtml(detail.label)}，現折 $${Number(detail.discount).toFixed(2)}</div>
        `).join('');
        return;
    }

    list.innerHTML = `
        <div class="checkout-promo-item">活動中：飲料任選 2 件 88 折</div>
        <div class="checkout-promo-item">活動中：零食任選 3 件折 $20</div>
    `;
};

app.loadCheckoutCoupons = async function () {
    const select = document.getElementById('checkoutCouponSelect');
    if (!select) return;
    select.innerHTML = '<option value="">載入中…</option>';
    try {
        const res = await api.get('../api/lucky_wheel.php?action=list_coupons&only_active=1');
        const coupons = res.coupons || [];
        app.checkoutCouponsCache = coupons;
        if (!coupons.length) {
            select.innerHTML = '<option value="">— 目前沒有可用優惠券 —</option>';
            return;
        }
        const subtotal = app.getCheckoutSubtotal();
        let html = '<option value="">— 不使用優惠券 —</option>';
        coupons.forEach(c => {
            const minPurchase = Number(c.min_purchase || 0);
            const eligible = subtotal >= minPurchase || minPurchase === 0;
            const discountLabel = c.discount_type === 'percent'
                ? `折 ${c.discount_value}%`
                : `折 $${Number(c.discount_value).toFixed(0)}`;
            const minLabel = minPurchase > 0 ? `（滿 $${Number(minPurchase).toFixed(0)} 可用）` : '';
            const ineligibleLabel = !eligible ? ' ── 消費未達門檻' : '';
            const desc = app.escapeHtml(c.description || c.coupon_code);
            const code = app.escapeHtml(c.coupon_code);
            html += `<option value="${code}"${!eligible ? ' disabled' : ''}>${desc} — ${discountLabel}${minLabel}${ineligibleLabel}</option>`;
        });
        select.innerHTML = html;
        if (!select._couponBound) {
            select._couponBound = true;
            select.addEventListener('change', () => {
                const code = select.value;
                const couponMsg = document.getElementById('checkoutCouponMessage');
                const previousCode = app.checkoutCoupon ? String(app.checkoutCoupon.coupon_code || '') : '';
                if (!code) {
                    app.checkoutCoupon = null;
                    if (couponMsg) { couponMsg.textContent = ''; couponMsg.className = 'message'; }
                    if (previousCode) {
                        app.toast('已取消優惠券', 'info', 1500);
                    }
                } else {
                    app.checkoutCoupon = app.checkoutCouponsCache.find(c => c.coupon_code === code) || null;
                    if (couponMsg && app.checkoutCoupon) {
                        couponMsg.textContent = `已套用優惠券 ${app.checkoutCoupon.coupon_code}`;
                        couponMsg.className = 'message success';
                        app.toast(`優惠券已套用：${app.checkoutCoupon.coupon_code}`, 'success');
                    }
                }
                app.updateCheckoutTotals();
            });
        }
    } catch (e) {
        select.innerHTML = '<option value="">優惠券載入失敗</option>';
    }
};

app.updateCouponSelectState = function (subtotal) {
    const select = document.getElementById('checkoutCouponSelect');
    if (!select || !app.checkoutCouponsCache.length) return;
    let selectedBecameIneligible = false;
    Array.from(select.options).forEach(opt => {
        if (!opt.value) return;
        const c = app.checkoutCouponsCache.find(cc => cc.coupon_code === opt.value);
        if (!c) return;
        const minPurchase = Number(c.min_purchase || 0);
        const eligible = subtotal >= minPurchase || minPurchase === 0;
        const discountLabel = c.discount_type === 'percent'
            ? `折 ${c.discount_value}%`
            : `折 $${Number(c.discount_value).toFixed(0)}`;
        const minLabel = minPurchase > 0 ? `（滿 $${Number(minPurchase).toFixed(0)} 可用）` : '';
        const ineligibleLabel = !eligible ? ' ── 消費未達門檻' : '';
        opt.text = `${c.description || c.coupon_code} — ${discountLabel}${minLabel}${ineligibleLabel}`;
        opt.disabled = !eligible;
        if (opt.selected && !eligible) selectedBecameIneligible = true;
    });
    if (selectedBecameIneligible) {
        select.value = '';
        app.checkoutCoupon = null;
        const couponMsg = document.getElementById('checkoutCouponMessage');
        if (couponMsg) {
            couponMsg.textContent = '購物金額已變動，所選優惠券不再符合使用資格，已自動取消';
            couponMsg.className = 'message error';
        }
        app.toast('消費金額變動，優惠券已自動取消', 'warning');
    }
};

app.calculateCouponDiscount = function (subtotal) {
    const coupon = app.checkoutCoupon;
    if (!coupon) return 0;
    const minPurchase = Number(coupon.min_purchase || 0);
    if (subtotal < minPurchase) return 0;

    if (coupon.discount_type === 'percent') {
        return subtotal * (Number(coupon.discount_value || 0) / 100);
    }
    return Number(coupon.discount_value || 0);
};

app.findActiveCouponByCode = async function (couponCode) {
    const res = await api.get('../api/lucky_wheel.php?action=list_coupons&only_active=1');
    const coupons = res.coupons || [];
    const normalized = String(couponCode || '').trim().toUpperCase();
    return coupons.find(c => String(c.coupon_code || '').trim().toUpperCase() === normalized) || null;
};

app.updateCheckoutTotals = function () {
    const shippingRadio = document.querySelector('input[name="shipping_method"]:checked');
    const paymentRadio = document.querySelector('input[name="payment_method"]:checked');
    const shipping = shippingRadio ? shippingRadio.value : '';
    const payment = paymentRadio ? paymentRadio.value : '';

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
    const promoDiscountRow = document.getElementById('checkoutPromoDiscountRow');
    const promoDiscountEl = document.getElementById('checkoutPromoDiscount');
    const couponDiscountRow = document.getElementById('checkoutCouponDiscountRow');
    const couponDiscountEl = document.getElementById('checkoutCouponDiscount');
    const discountRow = document.getElementById('checkoutDiscountRow');
    const discountEl = document.getElementById('checkoutDiscount');

    const { shipping_fee, payment_fee, freeShipping, threshold, baseFee } = app.getCheckoutFees(shipping, payment, subtotal);
    const promotionInfo = app.getCheckoutBundlePromotions();
    app.renderCheckoutPromotionTips(promotionInfo);

    if (!app._freeShippingToastState) {
        app._freeShippingToastState = {
            initialized: false,
            shipping: '',
            freeShipping: false
        };
    }

    const freeShipState = app._freeShippingToastState;
    if (!freeShipState.initialized) {
        freeShipState.initialized = true;
        freeShipState.shipping = shipping;
        freeShipState.freeShipping = freeShipping;
    } else {
        if (shipping && !freeShipState.freeShipping && freeShipping) {
            app.toast(`恭喜達成 ${shipping} 免運門檻！`, 'success');
        }
        freeShipState.shipping = shipping;
        freeShipState.freeShipping = freeShipping;
    }

    app.updateCouponSelectState(subtotal);
    let couponDiscount = app.calculateCouponDiscount(subtotal);
    if (couponDiscount > subtotal) couponDiscount = subtotal;
    let discount = promotionInfo.discount + couponDiscount;
    if (discount > subtotal) discount = subtotal;
    const grand = Math.max(0, subtotal + shipping_fee + payment_fee - discount);

    if (subtotalEl) subtotalEl.textContent = '$' + Number(subtotal).toFixed(2);
    if (shippingFeeEl) {
        if (freeShipping) {
            shippingFeeEl.innerHTML = '<s style="color:var(--muted);font-weight:400">$' + baseFee + '</s> <span style="color:#16a34a;font-weight:700">免運</span>';
        } else {
            shippingFeeEl.textContent = '$' + Number(shipping_fee).toFixed(2);
        }
    }
    if (paymentFeeEl) paymentFeeEl.textContent = '$' + Number(payment_fee).toFixed(2);

    const hintEl = document.getElementById('freeShippingHint');
    if (hintEl) {
        if (!shipping) {
            hintEl.style.display = 'none';
        } else if (freeShipping) {
            hintEl.textContent = '🎉 恭喜！本訂單享免運費';
            hintEl.className = 'free-shipping-hint achieved';
            hintEl.style.display = '';
        } else if (threshold !== null) {
            const needed = Math.max(0, threshold - subtotal).toFixed(0);
            hintEl.textContent = `🚚 再消費 $${needed} 即可享${shipping}免運（滿 $${threshold}）`;
            hintEl.className = 'free-shipping-hint';
            hintEl.style.display = '';
        } else {
            hintEl.style.display = 'none';
        }
    }
    if (promoDiscountRow && promoDiscountEl) {
        if (promotionInfo.discount > 0) {
            promoDiscountRow.style.display = '';
            promoDiscountEl.textContent = '-$' + Number(promotionInfo.discount).toFixed(2);
        } else {
            promoDiscountRow.style.display = 'none';
            promoDiscountEl.textContent = '-$0.00';
        }
    }
    if (couponDiscountRow && couponDiscountEl) {
        if (couponDiscount > 0) {
            couponDiscountRow.style.display = '';
            couponDiscountEl.textContent = '-$' + Number(couponDiscount).toFixed(2);
        } else {
            couponDiscountRow.style.display = 'none';
            couponDiscountEl.textContent = '-$0.00';
        }
    }
    if (discountRow && discountEl) {
        if (discount > 0) {
            discountRow.style.display = '';
            discountEl.textContent = '-$' + Number(discount).toFixed(2);
        } else {
            discountRow.style.display = 'none';
            discountEl.textContent = '-$0.00';
        }
    }
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
    app.checkoutCoupon = null;
    const msg = document.getElementById('checkoutMessage');
    const pickupStoreSection = document.getElementById('pickupStoreSection');
    const pickupStoreBrand = document.getElementById('pickupStoreBrand');
    const pickupStoreId = document.getElementById('pickupStoreId');
    const pickupStoreName = document.getElementById('pickupStoreName');
    const pickupStoreAddress = document.getElementById('pickupStoreAddress');
    const creditCardSection = document.getElementById('creditCardSection');
    const cardHolderName = document.getElementById('cardHolderName');
    const cardNumber = document.getElementById('cardNumber');
    const cardExpiry = document.getElementById('cardExpiry');
    const cardCvv = document.getElementById('cardCvv');
    const saveCardInfo = document.getElementById('saveCardInfo');
    const clearSavedCardBtn = document.getElementById('clearSavedCardBtn');
    const clearPickupStoreBtn = document.getElementById('clearPickupStoreBtn');
    let placing = false;

    const userKey = 'guest';
    const cardStorageKey = `vrshop_saved_card_${userKey}`;
    const pickupStorageKey = `vrshop_saved_pickup_${userKey}`;

    const togglePickupStoreSection = () => {
        const shipping = document.querySelector('input[name="shipping_method"]:checked')?.value || '';
        if (!pickupStoreSection) return;
        pickupStoreSection.style.display = shipping === '超商取貨' ? '' : 'none';
    };

    const toggleCreditCardSection = () => {
        const payment = document.querySelector('input[name="payment_method"]:checked')?.value || '';
        if (!creditCardSection) return;
        creditCardSection.style.display = payment === '信用卡' ? '' : 'none';
    };

    const applySavedCard = () => {
        try {
            const raw = localStorage.getItem(cardStorageKey);
            if (!raw) return;
            const saved = JSON.parse(raw);
            if (cardHolderName) cardHolderName.value = saved.holder || '';
            if (cardNumber) cardNumber.value = saved.number || '';
            if (cardExpiry) cardExpiry.value = saved.expiry || '';
            if (saveCardInfo) saveCardInfo.checked = true;
        } catch (err) {
            // Ignore parse errors and continue checkout.
        }
    };

    const applySavedPickupStore = () => {
        try {
            const raw = localStorage.getItem(pickupStorageKey);
            if (!raw) return;
            const saved = JSON.parse(raw);
            if (pickupStoreBrand && saved.brand) pickupStoreBrand.value = saved.brand;
            if (pickupStoreId && saved.store_id) pickupStoreId.value = saved.store_id;
            if (pickupStoreName && saved.store_name) pickupStoreName.value = saved.store_name;
            if (pickupStoreAddress && saved.store_address) pickupStoreAddress.value = saved.store_address;
        } catch (err) {
            // Ignore parse errors and continue checkout.
        }
    };

    const clearCardFields = () => {
        if (cardHolderName) cardHolderName.value = '';
        if (cardNumber) cardNumber.value = '';
        if (cardExpiry) cardExpiry.value = '';
        if (cardCvv) cardCvv.value = '';
        if (saveCardInfo) saveCardInfo.checked = false;
    };

    const clearPickupFields = () => {
        if (pickupStoreBrand) pickupStoreBrand.value = '';
        if (pickupStoreId) pickupStoreId.value = '';
        if (pickupStoreName) pickupStoreName.value = '';
        if (pickupStoreAddress) pickupStoreAddress.value = '';
    };

    if (cardNumber) {
        cardNumber.addEventListener('input', () => {
            const digits = cardNumber.value.replace(/\D/g, '').slice(0, 16);
            cardNumber.value = digits.replace(/(.{4})/g, '$1 ').trim();
        });
    }
    if (cardExpiry) {
        cardExpiry.addEventListener('input', () => {
            const digits = cardExpiry.value.replace(/\D/g, '').slice(0, 4);
            if (digits.length >= 3) {
                cardExpiry.value = `${digits.slice(0, 2)}/${digits.slice(2)}`;
            } else {
                cardExpiry.value = digits;
            }
        });
    }
    if (cardCvv) {
        cardCvv.addEventListener('input', () => {
            cardCvv.value = cardCvv.value.replace(/\D/g, '').slice(0, 4);
        });
    }

    if (clearSavedCardBtn) {
        clearSavedCardBtn.addEventListener('click', () => {
            localStorage.removeItem(cardStorageKey);
            clearCardFields();
            if (msg) {
                msg.textContent = '已清除記住的信用卡資訊';
                msg.className = 'message success';
            }
        });
    }

    if (clearPickupStoreBtn) {
        clearPickupStoreBtn.addEventListener('click', () => {
            localStorage.removeItem(pickupStorageKey);
            clearPickupFields();
            if (msg) {
                msg.textContent = '已清除常用超商門市';
                msg.className = 'message success';
            }
        });
    }

    const enforcePaymentRules = () => {
        // 超商取貨可使用貨到付款，此處僅保留介面同步。
        toggleCreditCardSection();
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
            togglePickupStoreSection();
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
    togglePickupStoreSection();
    toggleCreditCardSection();
    applySavedCard();
    applySavedPickupStore();

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

        if (shippingRadio.value === '超商取貨') {
            const brand = (pickupStoreBrand?.value || '').trim();
            const storeId = (pickupStoreId?.value || '').trim();
            const storeName = (pickupStoreName?.value || '').trim();
            if (!brand || !storeId || !storeName) {
                if (msg) {
                    msg.textContent = '超商取貨請填寫超商品牌、門市代碼與門市名稱';
                    msg.className = 'message error';
                }
                return;
            }
        }

        if (paymentRadio.value === '信用卡') {
            const holder = (cardHolderName?.value || '').trim();
            const numberDigits = (cardNumber?.value || '').replace(/\D/g, '');
            const expiryRaw = (cardExpiry?.value || '').trim();
            const cvv = (cardCvv?.value || '').trim();
            const expiryMatch = expiryRaw.match(/^(\d{2})\/(\d{2})$/);

            if (!holder || numberDigits.length !== 16 || !expiryMatch || cvv.length < 3) {
                if (msg) {
                    msg.textContent = '請完整填寫信用卡資訊（姓名、16 碼卡號、到期日、安全碼）';
                    msg.className = 'message error';
                }
                return;
            }

            const month = Number(expiryMatch[1]);
            const year = 2000 + Number(expiryMatch[2]);
            const now = new Date();
            const expDate = new Date(year, month, 0, 23, 59, 59);
            if (month < 1 || month > 12 || expDate < now) {
                if (msg) {
                    msg.textContent = '信用卡到期日無效或已過期';
                    msg.className = 'message error';
                }
                return;
            }

            if (saveCardInfo?.checked) {
                localStorage.setItem(cardStorageKey, JSON.stringify({
                    holder,
                    number: numberDigits.replace(/(.{4})/g, '$1 ').trim(),
                    expiry: expiryRaw
                }));
            } else {
                localStorage.removeItem(cardStorageKey);
            }
        }

        if (shippingRadio.value === '超商取貨') {
            localStorage.setItem(pickupStorageKey, JSON.stringify({
                brand: (pickupStoreBrand?.value || '').trim(),
                store_id: (pickupStoreId?.value || '').trim(),
                store_name: (pickupStoreName?.value || '').trim(),
                store_address: (pickupStoreAddress?.value || '').trim()
            }));
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
            if (shippingRadio.value === '超商取貨') {
                payload.pickup_store_brand = (pickupStoreBrand?.value || '').trim();
                payload.pickup_store_id = (pickupStoreId?.value || '').trim();
                payload.pickup_store_name = (pickupStoreName?.value || '').trim();
                payload.pickup_store_address = (pickupStoreAddress?.value || '').trim();
            }
            if (app.checkoutCoupon && app.checkoutCoupon.coupon_code) {
                payload.coupon_code = app.checkoutCoupon.coupon_code;
            }
            const res = await api.post('../api/orders.php?action=place_order', payload);
            if (res.success) {
                if (msg) {
                    msg.textContent = '下單成功！訂單編號：' + res.order_id;
                    msg.className = 'message success';
                }
                // 儲存優惠詳情到 sessionStorage
                sessionStorage.setItem('lastOrderPromotion', JSON.stringify({
                    order_id: res.order_id,
                    promotion_discount: res.promotion_discount || 0,
                    coupon_discount: res.coupon_discount || 0,
                    promotion_details: res.promotion_details || [],
                    shipping_fee: res.shipping_fee || 0,
                    payment_fee: res.payment_fee || 0,
                    subtotal: res.subtotal || 0,
                    total: res.total || 0
                }));
                app.loadCartCount();
                setTimeout(() => location.href = `order_success.php?order_id=${encodeURIComponent(res.order_id)}`, 800);
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
        case 'pending': return '已接單';  // 舊數據相容
        case 'accepted': return '已接單';
        case 'preparing': return '備貨中';
        case 'shipping': return '運送中';
        case 'done': return '已完成';
        case 'cancelled': return '已取消';
        case 'refund_pending': return '退單審核中';
        default: return status || '';
    }
};

app.normalizeOrderStatus = function (status, refundStatus) {
    if ((refundStatus || '') === 'pending') {
        return 'refund_pending';
    }
    if ((refundStatus || '') === 'approved') {
        return 'cancelled';
    }
    const raw = status || 'accepted';
    // Backward compatibility: old records may still store pending.
    return raw === 'pending' ? 'accepted' : raw;
};

app.escapeHtml = function (value) {
    return String(value ?? '').replace(/[&<>"']/g, (char) => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#39;'
    }[char]));
};

app.currentOrderFilter = 'all';

app.loadOrders = async function (statusFilter = app.currentOrderFilter || 'all') {
    const wrap = document.getElementById('ordersList');
    if (!wrap) return;
    const res = await api.get('../api/orders.php?action=list_my_orders');
    const orders = res.orders || [];
    const normalizedFilter = String(statusFilter || 'all');
    const filteredOrders = normalizedFilter === 'all'
        ? orders
        : orders.filter((o) => {
            const refundStatus = o.refund_status || '';
            const effectiveStatus = app.normalizeOrderStatus(o.status, refundStatus);
            return effectiveStatus === normalizedFilter;
        });
    if (!filteredOrders.length) {
        wrap.innerHTML = '<div class="orders-empty">目前還沒有訂單，先去挑選喜歡的商品吧。</div>';
        return;
    }
    let html = '<div class="orders-table-wrap"><table class="orders-table"><thead><tr><th>訂單編號</th><th>時間</th><th>金額</th><th>狀態</th><th>操作</th></tr></thead><tbody>';
    filteredOrders.forEach(o => {
        const refundStatus = o.refund_status || '';
        const effectiveStatus = app.normalizeOrderStatus(o.status, refundStatus);
        const statusClass = `status-${effectiveStatus}`;
        const canRequestRefund = ['pending', 'preparing', 'accepted'].includes(effectiveStatus) && !['pending', 'approved'].includes(refundStatus);
        const refundBtn = canRequestRefund
            ? `<button class="btn btn-secondary btn-sm request-refund" data-id="${o.order_id}">申請退單</button>`
            : '';
        html += `
            <tr data-order="${o.order_id}">
                <td>#${o.order_id}</td>
                <td>${app.escapeHtml(o.created_at)}</td>
                <td class="orders-amount">$${Number(o.total_price).toFixed(2)}</td>
                <td><span class="order-status ${statusClass}">${app.statusText(effectiveStatus)}</span></td>
                <td>
                    <div class="orders-actions">
                        <button class="btn btn-secondary btn-sm view-order" data-id="${o.order_id}">查看明細</button>
                        ${refundBtn}
                    </div>
                </td>
            </tr>
            <tr class="order-detail" data-detail="${o.order_id}" style="display:none;">
                <td colspan="5"><div class="detail-content"></div></td>
            </tr>
        `;
    });
    html += '</tbody></table></div>';
    wrap.innerHTML = html;
};

app.openRefundRequestDialog = function () {
    return new Promise((resolve) => {
        const old = document.getElementById('refundRequestModal');
        if (old) old.remove();

        const reasonOptions = [
            '買錯想重新下單',
            '改變心意暫不購買',
            '重複下單',
            '配送時間不符合需求',
            '其他'
        ];

        const modal = document.createElement('div');
        modal.id = 'refundRequestModal';
        modal.className = 'refund-modal-overlay';
        modal.innerHTML = `
            <div class="refund-modal" role="dialog" aria-modal="true" aria-labelledby="refundTitle">
                <h3 id="refundTitle">申請退單</h3>
                <p class="refund-modal-subtitle">請先選擇退單原因，再補充說明（選填）。</p>
                <label class="refund-modal-label" for="refundReasonSelect">退單原因</label>
                <select id="refundReasonSelect" class="refund-modal-select">
                    <option value="">請選擇原因</option>
                    ${reasonOptions.map(opt => `<option value="${app.escapeHtml(opt)}">${app.escapeHtml(opt)}</option>`).join('')}
                </select>
                <label class="refund-modal-label" for="refundNoteInput">其他說明（選填）</label>
                <textarea id="refundNoteInput" class="refund-modal-textarea" placeholder="可補充細節，例如想改訂哪個商品或時段"></textarea>
                <div class="refund-modal-actions">
                    <button type="button" class="btn btn-secondary" data-action="cancel">取消</button>
                    <button type="button" class="btn" data-action="confirm">送出申請</button>
                </div>
            </div>
        `;

        const cleanup = () => {
            modal.remove();
            document.body.classList.remove('modal-open');
        };

        document.body.classList.add('modal-open');
        document.body.appendChild(modal);

        modal.addEventListener('click', (e) => {
            const action = e.target.getAttribute('data-action');
            if (e.target === modal || action === 'cancel') {
                cleanup();
                resolve(null);
                return;
            }
            if (action !== 'confirm') return;

            const reason = String(modal.querySelector('#refundReasonSelect')?.value || '').trim();
            const note = String(modal.querySelector('#refundNoteInput')?.value || '').trim();
            if (!reason) {
                alert('請先選擇退單原因');
                return;
            }

            cleanup();
            resolve({ reason, note });
        });
    });
};

app.initOrdersPage = function () {
    const wrap = document.getElementById('ordersList');
    if (!wrap) return;
    const filtersWrap = document.getElementById('ordersFilters');
    if (filtersWrap) {
        filtersWrap.addEventListener('click', async (e) => {
            const btn = e.target.closest('.orders-filter-btn');
            if (!btn) return;
            const status = btn.getAttribute('data-status') || 'all';
            app.currentOrderFilter = status;
            filtersWrap.querySelectorAll('.orders-filter-btn').forEach((node) => {
                node.classList.toggle('is-active', node === btn);
            });
            await app.loadOrders(status);
        });
    }

    app.loadOrders(app.currentOrderFilter);
    wrap.addEventListener('click', async (e) => {
        const btn = e.target.closest('.view-order');
        if (btn) {
            const orderId = btn.getAttribute('data-id');
            const detailRow = wrap.querySelector(`tr[data-detail="${orderId}"]`);
            const detailBox = detailRow ? detailRow.querySelector('.detail-content') : null;
            if (!detailRow || !detailBox) return;
            if (detailRow.style.display !== 'none') {
                detailRow.style.display = 'none';
                btn.textContent = '查看明細';
                return;
            }
            detailBox.innerHTML = '<div class="order-detail-card">明細載入中...</div>';
            detailRow.style.display = '';
            const res = await api.get(`../api/orders.php?action=get_order_detail&order_id=${orderId}`);
            if (res.order) {
                const items = res.items || [];
                const effectiveStatus = app.normalizeOrderStatus(res.order.status, res.order.refund_status);
                const statusClass = `status-${effectiveStatus}`;
                let list = '<div class="order-detail-card">';
                list += '<div class="order-detail-meta">';
                list += `<div><span>狀態</span><strong><span class="order-status ${statusClass}">${app.statusText(effectiveStatus)}</span></strong></div>`;
                list += `<div><span>金額</span><strong>$${Number(res.order.total_price).toFixed(2)}</strong></div>`;
                list += `<div><span>支付方式</span><strong>${app.escapeHtml(res.order.payment_method || '未提供')}</strong></div>`;
                list += `<div><span>送貨方式</span><strong>${app.escapeHtml(res.order.shipping_method || '未提供')}</strong></div>`;
                list += `<div><span>收件資訊</span><strong>${app.escapeHtml(res.order.ship_address_line || '未提供')}</strong></div>`;
                list += '</div>';
                if (res.order.refund_status && res.order.refund_status !== 'approved') {
                    list += `<p class="orders-refund-reason">原因：${app.escapeHtml(res.order.refund_reason || '未提供')}</p>`;
                    if (res.order.refund_note) {
                        list += `<p class="orders-refund-reason">補充：${app.escapeHtml(res.order.refund_note)}</p>`;
                    }
                }
                list += '<p class="order-items-title">商品明細</p>';
                list += '<ul class="order-items-list">';
                items.forEach(i => {
                    list += `<li><span>${app.escapeHtml(i.name)} × ${Number(i.quantity)}</span><strong>$${Number(i.price).toFixed(2)}</strong></li>`;
                });
                list += '</ul>';
                list += '</div>';
                detailBox.innerHTML = list;
                detailRow.style.display = '';
                btn.textContent = '收合明細';
            }
            return;
        }

        const refundBtn = e.target.closest('.request-refund');
        if (!refundBtn) return;
        const orderId = Number(refundBtn.getAttribute('data-id') || 0);
        if (!orderId || !Number.isInteger(orderId) || orderId <= 0) {
            console.error('Invalid order_id:', orderId);
            alert('訂單編號無效，請重新整理頁面後再試');
            return;
        }

        const refundData = await app.openRefundRequestDialog();
        if (!refundData) return;

        refundBtn.disabled = true;
        try {
            const res = await api.post('../api/orders.php?action=request_refund', {
                order_id: String(orderId),
                reason: refundData.reason,
                note: refundData.note
            });
            if (res.success) {
                alert('已送出退單申請，請等待審核');
                // 延遲一下再重新載入，確保後端資料已更新
                setTimeout(() => app.loadOrders(), 500);
            } else {
                // 特別處理「找不到此訂單」錯誤
                if (res.error === '找不到此訂單') {
                    alert('此訂單不存在或無法申請退單，請重新整理頁面');
                    // 重新載入訂單清單
                    app.loadOrders();
                } else {
                    alert(res.error || '退單申請失敗');
                }
            }
        } catch (err) {
            console.error('Request refund error:', err);
            alert('退單申請失敗，請稍後再試');
        } finally {
            refundBtn.disabled = false;
        }
    });
};

app.initOrderSuccessPage = async function () {
    const detailWrap = document.getElementById('orderSuccessDetail');
    if (!detailWrap) return;

    const orderIdParam = new URLSearchParams(location.search).get('order_id');
    const orderId = Number(orderIdParam || 0);
    const orderIdEl = document.getElementById('orderSuccessOrderId');
    if (orderIdEl && orderId > 0) {
        orderIdEl.textContent = `#${orderId}`;
    }

    if (!Number.isInteger(orderId) || orderId <= 0) {
        detailWrap.innerHTML = '<div class="order-detail-card">查無訂單編號，請至「我的訂單」查看。</div>';
        return;
    }

    try {
        const res = await api.get(`../api/orders.php?action=get_order_detail&order_id=${orderId}`);
        if (!res.order) {
            const errorMsg = res.error === '找不到此訂單' 
                ? '此訂單不存在或無法訪問'
                : '訂單明細載入失敗';
            detailWrap.innerHTML = `<div class="order-detail-card">${errorMsg}，請至「我的訂單」查看。<br><br><button class="btn btn-secondary" onclick="location.href='./orders.php'">前往我的訂單</button></div>`;
            return;
        }

        const items = res.items || [];
        const statusClass = `status-${res.order.status || 'accepted'}`;
        let html = '<div class="order-detail-card">';
        html += '<div class="order-detail-meta">';
        html += `<div><span>狀態</span><strong><span class="order-status ${statusClass}">${app.statusText(res.order.status)}</span></strong></div>`;
        html += `<div><span>金額</span><strong>$${Number(res.order.total_price).toFixed(2)}</strong></div>`;
        html += `<div><span>支付方式</span><strong>${app.escapeHtml(res.order.payment_method || '未提供')}</strong></div>`;
        html += `<div><span>送貨方式</span><strong>${app.escapeHtml(res.order.shipping_method || '未提供')}</strong></div>`;
        html += `<div><span>收件資訊</span><strong>${app.escapeHtml(res.order.ship_address_line || '未提供')}</strong></div>`;
        html += '</div>';
        html += '<p class="order-items-title">商品明細</p>';
        html += '<ul class="order-items-list">';
        items.forEach(i => {
            html += `<li><span>${app.escapeHtml(i.name)} × ${Number(i.quantity)}</span><strong>$${Number(i.price).toFixed(2)}</strong></li>`;
        });
        html += '</ul>';
        html += '</div>';
        detailWrap.innerHTML = html;
    } catch (e) {
        detailWrap.innerHTML = '<div class="order-detail-card">訂單明細載入失敗，請至「我的訂單」查看。</div>';
    }
};

document.addEventListener('DOMContentLoaded', () => {
    app.loadCartCount();
    app.initCartPage();
    app.initCheckoutPage();
    app.initOrdersPage();
    app.initOrderSuccessPage();
    maybeShowLuckyWheelAfterLogin();
});
