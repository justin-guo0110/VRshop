<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$currentUser = $_SESSION['user'] ?? null;
$pageTitle = 'VR Mall - 您的虛擬實境購物天堂';
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>

    <!-- 共用樣式 -->
    <link rel="stylesheet" href="../public/css/style.css">

    <!-- 首頁專用樣式 -->
    <link rel="stylesheet" href="../public/css/home.css">

    <?php if (empty($disableChatWidget)): ?>
        <link rel="stylesheet" href="../public/css/chat.css">
    <?php endif; ?>
</head>
<body>
    <header class="site-header">
        <div class="container header-inner">

            <a href="./index.php" class="logo" style="text-decoration: none;">VR Mall</a>

            <nav class="nav">
                <a href="./index.php">首頁</a>
                <a href="./products.php">商品</a>
                <a href="./profile.php">個人資料</a>

                <?php if ($currentUser && ($currentUser['role'] ?? '') === 'member'): ?>
                    <a href="./cart.php">購物車</a>
                    <a href="./orders.php">訂單</a>
                <?php endif; ?>

                <?php if ($currentUser && ($currentUser['role'] ?? '') === 'admin'): ?>
                    <a href="./admin.php">管理</a>
                <?php endif; ?>
            </nav>

            <div class="user-info">
                <?php if ($currentUser): ?>
                    <span>歡迎 <?php echo htmlspecialchars($currentUser['name'] ?? $currentUser['email']); ?></span>
                    <button class="btn btn-secondary" id="logoutBtn">登出</button>
                <?php else: ?>
                    <a class="btn" href="./login.php">登入</a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <main class="container" style="padding-top: 0; display: grid; grid-template-columns: 200px 1fr; gap: 20px;">
        <!-- 左側廣告框架 -->
        <aside class="sidebar-ads-box">
            <a href="./products.php" class="sidebar-ad">
                <strong>限時優惠</strong>
                <span>VR 頭盔滿 NT$10,000 折 NT$1,000</span>
            </a>
            <a href="./products.php" class="sidebar-ad">
                <strong>週末加碼</strong>
                <span>指定周邊 2 件 9 折</span>
            </a>
            <a href="./products.php" class="sidebar-ad">
                <strong>新品上市</strong>
                <span>最新 VR 遊戲內容上線</span>
            </a>
            <a href="./products.php" class="sidebar-ad">
                <strong>會員優惠</strong>
                <span>累計購物點數可折抵</span>
            </a>
        </aside>

        <!-- 主內容區 -->
        <div>
        <section class="promo-slider" id="promoSlider">
            <div class="promo-slider-inner" id="promoSliderInner">
                <div class="promo-slide">
                    <div class="promo-slide-text">
                        <h3>開站慶｜全館滿額折扣</h3>
                        <p>單筆滿 NT$5,000 折 NT$300，滿 NT$10,000 折 NT$1,000。</p>
                    </div>
                    <div class="promo-slide-cta">
                        <a href="./products.php" class="btn btn-secondary">馬上逛逛</a>
                    </div>
                </div>
                <div class="promo-slide">
                    <div class="promo-slide-text">
                        <h3>VR 套裝組合優惠</h3>
                        <p>主機 + 控制器 + 周邊一次帶走，組合價更划算。</p>
                    </div>
                    <div class="promo-slide-cta">
                        <a href="./products.php" class="btn btn-secondary">查看套裝</a>
                    </div>
                </div>
                <div class="promo-slide">
                    <div class="promo-slide-text">
                        <h3>會員專屬體驗活動</h3>
                        <p>登入會員即可報名線下 VR 體驗會，名額有限。</p>
                    </div>
                    <div class="promo-slide-cta">
                        <a href="./profile.php" class="btn btn-secondary">前往會員中心</a>
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
        <section class="hero-section" style="margin: 0; padding: 80px 16px;">
            <div style="max-width: 1100px; margin: 0 auto;">
                <h1>歡迎來到 VR Mall</h1>
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
                <a href="./products.php" class="btn">查看更多商品</a>
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
            <p>© <?php echo date('Y'); ?> VR Mall</p>
        </div>
    </footer>


    <!-- 共用 JS -->
    <script src="../public/js/app.js"></script>

    <?php if (empty($disableChatWidget)): ?>
        <script src="../public/js/chat.js"></script>
    <?php endif; ?>

    <script>
        // 載入熱門商品
        async function loadFeaturedProducts() {
            try {
                const response = await fetch('../api/shop.php?action=search_products');
                const data = await response.json();
                const container = document.getElementById('featuredProducts');

                if (!data.products || data.products.length === 0) {
                    container.innerHTML = '<p style="text-align:center;padding:40px;">暫無商品</p>';
                    return;
                }

                const products = data.products.slice(0, 6);
                container.innerHTML = '';

                products.forEach(product => {
                    const card = document.createElement('div');
                    card.className = 'product-card';
                    card.innerHTML = `
                        <img src="${product.image_url || 'https://via.placeholder.com/300x200?text=Product'}" alt="${product.name}">
                        <h3>${product.name}</h3>
                        <p class="price">$${Number(product.price).toFixed(2)}</p>
                        <p style="color:#666;font-size:0.9rem;">${product.category || ''}</p>
                        <a class="btn" href="./product_detail.php?product_id=${product.product_id}">查看詳情</a>
                    `;
                    container.appendChild(card);
                });

            } catch (error) {
                console.error('載入商品失敗:', error);
                document.getElementById('featuredProducts').innerHTML =
                    '<p style="text-align:center;padding:40px;color:#d14343;">載入商品時發生錯誤</p>';
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
            initPromoSlider();
        });
    </script>

</body>
</html>
