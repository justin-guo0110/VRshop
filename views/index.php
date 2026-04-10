<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$currentUser = $_SESSION['user'] ?? null;
$pageTitle = 'VR Shopping Mall - 您的虛擬實境購物天堂';
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>

    <!-- 共用樣式 -->
    <link rel="stylesheet" href="../public/css/style.css">
    <link rel="stylesheet" href="../public/css/enhance.css?v=<?php echo filemtime(__DIR__ . '/../public/css/enhance.css'); ?>">

    <!-- 首頁專用樣式 -->
    <link rel="stylesheet" href="../public/css/home.css?v=<?php echo filemtime(__DIR__ . '/../public/css/home.css'); ?>">

    <?php if (empty($disableChatWidget)): ?>
        <link rel="stylesheet" href="../public/css/chat.css">
    <?php endif; ?>
</head>
<body>
    <header class="site-header">
        <div class="container header-inner">

            <a href="./index.php" class="logo" style="text-decoration: none;">VR Shopping Mall</a>

            <nav class="nav">
                <?php if ($currentUser && ($currentUser['role'] ?? '') === 'admin'): ?>
                    <a href="./admin.php?page=dashboard">📊 儀表板</a>
                    <a href="./admin.php?page=orders">📋 訂單管理</a>
                    <a href="./admin.php?page=products">📦 商品管理</a>
                    <a href="./admin.php?page=inventory">📚 庫存管理</a>
                    <a href="./admin.php?page=featured_products">⭐ 熱門商品管理</a>
                    <a href="./admin.php?page=sidebar_ads">🖼️ 側邊廣告管理</a>
                    <a href="./admin.php?page=chat">💬 客服系統</a>
                <?php else: ?>
                    <a href="./index.php">首頁</a>
                    <a href="./products.php">商品</a>

                    <?php if ($currentUser && ($currentUser['role'] ?? '') === 'member'): ?>
                        <a href="./cart.php">購物車</a>
                                                <?php
                            $memberId = $currentUser['member_id'] ?? 0;
                            echo "<!-- debug member_id: " . $memberId . " -->"; // 在瀏覽器原始碼看
                            $vrUrl = "vrmall://launch?member_id=" . $memberId;
                        ?>
                        <a class="btn btn-vr" href="<?php echo $vrUrl; ?>">🥽 進入 VR 商城</a>
                    <?php endif; ?>
                <?php endif; ?>
            </nav>

            <div class="user-info">
                <?php if ($currentUser): ?>
                    <div class="nav-dropdown user-welcome-dropdown" id="userWelcomeDropdown">
                        <button type="button" class="nav-dropdown-toggle welcome-toggle" id="userWelcomeToggle" aria-expanded="false" aria-controls="userWelcomeMenu">
                            歡迎 <?php echo htmlspecialchars($currentUser['name'] ?? $currentUser['email']); ?>
                            <span style="font-size:11px;opacity:.9;">▼</span>
                        </button>
                        <div class="nav-dropdown-menu user-dropdown-menu" id="userWelcomeMenu">
                            <a href="./profile.php">個人資料</a>
                            <a href="./coupons.php">我的優惠券</a>
                            <?php if (($currentUser['role'] ?? '') !== 'admin'): ?>
                                <a href="./orders.php">我的訂單</a>
                            <?php endif; ?>
                            <div class="coupon-dropdown-block">
                                <div class="coupon-dropdown-title">我的優惠券</div>
                                <div id="headerCouponList" class="coupon-dropdown-list">
                                    <p class="coupon-dropdown-loading">載入中...</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <button class="btn btn-secondary" id="logoutBtn" type="button">登出</button>
                <?php else: ?>
                    <a class="btn" href="./login.php">登入</a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <main class="container home-layout">
        <!-- 左側廣告框架 -->
        <aside class="sidebar-ads-box" id="sidebarAdsBox">
            <a href="./products.php" class="sidebar-ad sidebar-ad-image">
                <div class="sidebar-ad-placeholder">歡迎諮詢投廣</div>
            </a>
            <a href="./products.php" class="sidebar-ad sidebar-ad-image">
                <div class="sidebar-ad-placeholder">歡迎諮詢投廣</div>
            </a>
            <a href="./products.php" class="sidebar-ad sidebar-ad-image">
                <div class="sidebar-ad-placeholder">歡迎諮詢投廣</div>
            </a>
            <a href="./products.php" class="sidebar-ad sidebar-ad-image">
                <div class="sidebar-ad-placeholder">歡迎諮詢投廣</div>
            </a>
        </aside>

        <!-- 主內容區 -->
        <div class="home-main-content">
        <section class="promo-slider" id="promoSlider">
            <div class="promo-slider-inner" id="promoSliderInner">
                <div class="promo-slide promo-slide-new-member">
                    <div class="promo-slide-media" aria-hidden="true">
                        <img src="../public/images/promo/new-member-first-order-9off.svg" alt="新會員首購9折券促銷素材">
                    </div>
                </div>
                <div class="promo-slide promo-slide-image-only promo-slide-free-shipping">
                    <div class="promo-slide-media" aria-hidden="true">
                        <img src="../public/images/promo/free-shipping-threshold.svg" alt="滿額免運自動套用促銷素材">
                    </div>
                </div>
                <div class="promo-slide promo-slide-image-only promo-slide-combo-deal">
                    <div class="promo-slide-media" aria-hidden="true">
                        <img src="../public/images/promo/combo-deal-snacks-drinks.svg" alt="組合優惠同步開跑促銷素材">
                    </div>
                </div>
            </div>
            <div class="promo-dots" id="promoDots">
                <button class="promo-dot active" data-index="0"></button>
                <button class="promo-dot" data-index="1"></button>
                <button class="promo-dot" data-index="2"></button>
            </div>
        </section>
        
        <!-- Hero 區域 -->
        <section class="hero-section home-hero">
            <div style="max-width: 1100px; margin: 0 auto;">
                <h1>歡迎來到 VR Shopping Mall</h1>
                <p>探索最新的虛擬實境產品，體驗未來購物的無限可能</p>
                <div class="hero-buttons">
                    <a href="./products.php" class="btn">瀏覽商品</a>

                    <?php if (!$currentUser): ?>
                        <a href="./login.php" class="btn btn-secondary">立即登入</a>
                    <?php else: ?>
                        <a href="./profile.php" class="btn btn-secondary">會員中心</a>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <!-- 熱門商品 -->
        <section class="container home-products">
            <h2 class="section-title">熱門商品</h2>
            <div id="featuredProducts" class="product-grid">
                <p style="text-align: center; padding: 40px;">載入中...</p>
            </div>
            <div style="text-align: center; margin-top: 30px;">
                <a href="./products.php" class="btn">檢視更多商品</a>
            </div>
        </section>

        <!-- 促銷 Banner -->
        <section class="container">
            <div class="promo-banner">
                <h2>準備好開始您的VR之旅了嗎？</h2>
                <p>立即加入會員，享受專屬優惠與服務</p>

                <?php if (!$currentUser): ?>
                    <a href="./login.php" class="btn" style="background:white;color:var(--primary);">立即註冊</a>
                <?php else: ?>
                    <a href="./products.php" class="btn" style="background:white;color:var(--primary);">開始購物</a>
                <?php endif; ?>
            </div>
        </section>
        </div>

    </main>

    <footer class="site-footer">
        <div class="container">
            <div class="footer-grid">
                <div class="footer-brand">© <?php echo date('Y'); ?> VR Shopping Mall</div>
                <div class="footer-contact">
                    <p><strong>聯絡我們：</strong><a href="#" class="js-open-chat">開啟客服聊天室</a></p>
                    <p><strong>電話：</strong>02-2905-2000</p>
                    <p><strong>地址：</strong>天主教輔仁大學-新北市新莊區中正路510號</p>
                    <p><strong>電子郵件：</strong><a href="mailto:pubwww@mail.fju.edu.tw">pubwww@mail.fju.edu.tw</a></p>
                </div>
            </div>
        </div>
    </footer>


    <!-- 共用 JS -->
    <script src="../public/js/app.js?v=<?php echo filemtime(__DIR__ . '/../public/js/app.js'); ?>"></script>

    <?php if (empty($disableChatWidget)): ?>
        <script src="../public/js/chat.js"></script>
    <?php endif; ?>

    <script>
        // 載入熱門商品
        async function loadFeaturedProducts() {
            try {
                const response = await fetch('../api/shop.php?action=featured_products');
                const data = await response.json();
                const container = document.getElementById('featuredProducts');

                if (!data.products || data.products.length === 0) {
                    container.innerHTML = '<p style="text-align:center;padding:40px;">暫無商品</p>';
                    return;
                }

                const products = data.products.slice(0, 6);
                container.innerHTML = '';

                products.forEach((product, index) => {
                    const featuredLabel = product.featured_badge || '精選推薦';
                    const card = document.createElement('div');
                    card.className = 'product-card';
                    card.innerHTML = `
                        <div class="featured-card-media">
                            <div class="featured-card-badges">
                                <span class="featured-badge featured-badge-primary">${featuredLabel}</span>
                            </div>
                            <img src="${typeof fixImageUrl === 'function' ? fixImageUrl(product.image_url) : (product.image_url || 'https://via.placeholder.com/300x200?text=No+Image')}" alt="${product.name}" onerror="this.onerror=null;this.src='https://via.placeholder.com/300x200?text=No+Image';">
                        </div>
                        <div class="featured-card-body">
                            <h3>${product.name}</h3>
                            <p class="price">NT$ ${Number(product.price).toFixed(0)}</p>
                            <div class="featured-meta-row">
                                <p class="featured-category">${product.category || '未分類'}</p>
                                <span class="featured-chip">快速出貨</span>
                            </div>
                            <a class="btn btn-secondary" href="./product_detail.php?product_id=${product.product_id}">檢視詳情</a>
                        </div>
                    `;
                    container.appendChild(card);
                });

            } catch (error) {
                console.error('載入商品失敗:', error);
                document.getElementById('featuredProducts').innerHTML =
                    '<p style="text-align:center;padding:40px;color:#d14343;">載入商品時發生錯誤</p>';
            }
        }

        function renderSidebarAds(ads) {
            const box = document.getElementById('sidebarAdsBox');
            if (!box) return;

            const list = Array.isArray(ads) ? ads.slice(0, 4) : [];
            if (!list.length) return;

            box.innerHTML = '';
            list.forEach((row, idx) => {
                const imageUrl = String(row.image_url || '').trim();
                const linkUrl = String(row.link_url || './products.php').trim();
                const alt = String(row.alt || `側邊廣告 ${idx + 1}`).trim();

                const a = document.createElement('a');
                a.className = 'sidebar-ad sidebar-ad-image';
                a.href = linkUrl || './products.php';

                if (!imageUrl) {
                    a.innerHTML = '<div class="sidebar-ad-placeholder">歡迎諮詢投廣</div>';
                    box.appendChild(a);
                    return;
                }

                const img = document.createElement('img');
                img.src = imageUrl;
                img.alt = alt || `側邊廣告 ${idx + 1}`;
                img.loading = 'lazy';
                img.onerror = function () {
                    const parent = this.parentElement;
                    if (!parent) return;
                    parent.innerHTML = '<div class="sidebar-ad-placeholder">歡迎諮詢投廣</div>';
                };

                a.appendChild(img);
                box.appendChild(a);
            });

            if (!box.children.length) {
                for (let i = 0; i < 4; i++) {
                    const a = document.createElement('a');
                    a.className = 'sidebar-ad sidebar-ad-image';
                    a.href = './products.php';
                    a.innerHTML = '<div class="sidebar-ad-placeholder">歡迎諮詢投廣</div>';
                    box.appendChild(a);
                }
            }
        }

        async function loadSidebarAds() {
            try {
                const response = await fetch('../api/shop.php?action=sidebar_ads');
                const data = await response.json();
                if (data && data.success && Array.isArray(data.sidebar_ads)) {
                    renderSidebarAds(data.sidebar_ads);
                }
            } catch (error) {
                console.error('載入側邊廣告失敗:', error);
            }
        }

        function initPromoSlider() {
            const inner = document.getElementById('promoSliderInner');
            const dots = Array.from(document.querySelectorAll('#promoDots .promo-dot'));
            if (!inner || !dots.length) return;
            let current = 0;
            const total = dots.length;

            function goTo(index) {
                current = (index + total) % total;
                inner.style.transform = 'translateX(' + (-100 * current) + '%)';
                dots.forEach((d, i) => {
                    d.classList.toggle('active', i === current);
                });
            }

            dots.forEach(d => {
                d.addEventListener('click', () => {
                    const idx = parseInt(d.dataset.index, 10) || 0;
                    goTo(idx);
                });
            });

            let timer = setInterval(() => goTo(current + 1), 6000);
            const slider = document.getElementById('promoSlider');
            if (slider) {
                slider.addEventListener('mouseenter', () => clearInterval(timer));
                slider.addEventListener('mouseleave', () => {
                    timer = setInterval(() => goTo(current + 1), 6000);
                });
            }
        }

        document.addEventListener('DOMContentLoaded', function () {
            loadFeaturedProducts();
            loadSidebarAds();
            initPromoSlider();

            document.querySelectorAll('.js-open-chat').forEach(function (el) {
                el.addEventListener('click', function (e) {
                    e.preventDefault();
                    const toggle = document.getElementById('chatToggleBtn');
                    const win = document.getElementById('chatWindow');
                    if (!toggle) {
                        alert('客服聊天室目前未啟用');
                        return;
                    }
                    if (!win || !win.classList.contains('open')) {
                        toggle.click();
                    }
                    const input = document.getElementById('chatInput');
                    if (input) {
                        setTimeout(function () { input.focus(); }, 150);
                    }
                });
            });
        });
    </script>

</body>
</html>
