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
    <link rel="stylesheet" href="public/css/style.css">
    <style>
        /* 首頁專用樣式 */
        .hero-section {
            background: linear-gradient(135deg, #ff7a18 0%, #ff9b32 100%);
            color: white;
            padding: 80px 20px;
            text-align: center;
            margin: 16px -16px 40px -16px;
            border-radius: 0;
        }
        .hero-section h1 {
            font-size: 3rem;
            margin: 0 0 20px;
            font-weight: 700;
        }
        .hero-section p {
            font-size: 1.3rem;
            margin: 0 0 30px;
            opacity: 0.95;
        }
        .hero-buttons {
            display: flex;
            gap: 16px;
            justify-content: center;
            flex-wrap: wrap;
        }
        .hero-buttons .btn {
            background: white;
            color: var(--primary);
            padding: 14px 28px;
            font-size: 1.1rem;
        }
        .hero-buttons .btn:hover {
            background: #f0f0f0;
            transform: translateY(-2px);
        }
        .hero-buttons .btn-secondary {
            background: transparent;
            border: 2px solid white;
            color: white;
        }
        .hero-buttons .btn-secondary:hover {
            background: rgba(255,255,255,0.1);
        }

        .features-section {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 24px;
            margin: 40px 0;
        }
        .feature-card {
            text-align: center;
            padding: 30px 20px;
            background: var(--card);
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 24px rgba(0,0,0,0.12);
        }
        .feature-icon {
            font-size: 3rem;
            margin-bottom: 16px;
        }
        .feature-card h3 {
            margin: 0 0 12px;
            color: var(--secondary);
        }
        .feature-card p {
            color: #666;
            margin: 0;
            line-height: 1.6;
        }

        .section-title {
            text-align: center;
            font-size: 2.2rem;
            margin: 60px 0 40px;
            color: var(--secondary);
        }
        .section-title::after {
            content: '';
            display: block;
            width: 60px;
            height: 4px;
            background: var(--primary);
            margin: 16px auto 0;
            border-radius: 2px;
        }

        .home-products {
            margin: 40px 0;
        }

        .promo-banner {
            background: linear-gradient(135deg, var(--secondary) 0%, #2d3e50 100%);
            color: white;
            padding: 40px 20px;
            border-radius: 12px;
            text-align: center;
            margin: 40px 0;
        }
        .promo-banner h2 {
            margin: 0 0 16px;
            font-size: 2rem;
        }
        .promo-banner p {
            font-size: 1.1rem;
            margin: 0 0 24px;
            opacity: 0.9;
        }

        @media (max-width: 768px) {
            .hero-section h1 {
                font-size: 2rem;
            }
            .hero-section p {
                font-size: 1.1rem;
            }
            .section-title {
                font-size: 1.8rem;
            }
        }
    </style>
</head>
<body>
    <header class="site-header">
        <div class="container header-inner">
            <a href="index.php" class="logo" style="text-decoration: none;">VR Mall</a>
            <nav class="nav">
                <a href="index.php">首頁</a>
                <a href="views/products.php">商品</a>
                <a href="views/profile.php">個人資料</a>
                
                <?php if ($currentUser && ($currentUser['role'] ?? '') === 'member'): ?>
                    <a href="views/cart.php">購物車</a>
                    <a href="views/orders.php">訂單</a>
                <?php endif; ?>

                <?php if ($currentUser && ($currentUser['role'] ?? '') === 'admin'): ?>
                    <a href="views/admin.php">管理</a>
                <?php endif; ?>
            </nav>
            <div class="user-info">
                <?php if ($currentUser): ?>
                    <span>歡迎 <?php echo htmlspecialchars($currentUser['name'] ?? $currentUser['email']); ?></span>
                    <button class="btn btn-secondary" id="logoutBtn">登出</button>
                <?php else: ?>
                    <a class="btn" href="views/login.php">登入</a>
                <?php endif; ?>
            </div>
        </div>
    </header>
    <main class="container" style="padding-top: 0;">

    <!-- Hero Section -->
    <section class="hero-section" style="margin: 0; padding: 80px 16px;">
        <div style="max-width: 1100px; margin: 0 auto;">
            <h1>歡迎來到 VR Mall</h1>
            <p>探索最新的虛擬實境產品，體驗未來購物的無限可能</p>
            <div class="hero-buttons">
                <a href="views/products.php" class="btn">瀏覽商品</a>
                <?php if (!$currentUser): ?>
                    <a href="views/login.php" class="btn btn-secondary">立即登入</a>
                <?php else: ?>
                    <a href="views/profile.php" class="btn btn-secondary">會員中心</a>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="container">
        <h2 class="section-title">為什麼選擇 VR Mall</h2>
        <div class="features-section">
            <div class="feature-card">
                <div class="feature-icon">🛒</div>
                <h3>豐富商品</h3>
                <p>精選優質VR設備與配件，滿足您的所有需求</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">🚚</div>
                <h3>快速配送</h3>
                <p>提供宅配與超商取貨服務，快速安全送到您手中</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">💳</div>
                <h3>安全支付</h3>
                <p>支援多種付款方式，交易安全有保障</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">🎧</div>
                <h3>專業服務</h3>
                <p>專業客服團隊，隨時為您提供協助與支援</p>
            </div>
        </div>
    </section>

    <!-- Featured Products -->
    <section class="container home-products">
        <h2 class="section-title">熱門商品</h2>
        <div id="featuredProducts" class="product-grid">
            <p style="text-align: center; padding: 40px;">載入中...</p>
        </div>
        <div style="text-align: center; margin-top: 30px;">
            <a href="views/products.php" class="btn">查看更多商品</a>
        </div>
    </section>

    <!-- Promo Banner -->
    <section class="container">
        <div class="promo-banner">
            <h2>準備好開始您的VR之旅了嗎？</h2>
            <p>立即註冊會員，享受專屬優惠與服務</p>
            <?php if (!$currentUser): ?>
                <a href="views/login.php" class="btn" style="background: white; color: var(--primary);">立即註冊</a>
            <?php else: ?>
                <a href="views/products.php" class="btn" style="background: white; color: var(--primary);">開始購物</a>
            <?php endif; ?>
        </div>
    </section>

    </main>
    <footer class="site-footer">
        <div class="container">
            <p>© <?php echo date('Y'); ?> VR Mall</p>
        </div>
    </footer>
    <?php if (empty($disableChatWidget)): ?>
        <link rel="stylesheet" href="public/css/chat.css">
    <?php endif; ?>
    <script src="public/js/app.js"></script>
    <?php if (empty($disableChatWidget)): ?>
        <script src="public/js/chat.js"></script>
    <?php endif; ?>

    <script>
        // 載入熱門商品
        async function loadFeaturedProducts() {
            try {
                const response = await fetch('api/shop.php?action=search_products');
                const data = await response.json();
                const container = document.getElementById('featuredProducts');
                
                if (!data.products || data.products.length === 0) {
                    container.innerHTML = '<p style="text-align: center; padding: 40px;">暫無商品</p>';
                    return;
                }

                // 只顯示前6個商品
                const products = data.products.slice(0, 6);
                container.innerHTML = '';
                
                products.forEach(product => {
                    const card = document.createElement('div');
                    card.className = 'product-card';
                    card.innerHTML = `
                        <img src="${product.image_url || 'https://via.placeholder.com/300x200?text=Product'}" alt="${product.name}">
                        <h3>${product.name}</h3>
                        <p class="price">$${Number(product.price).toFixed(2)}</p>
                        <p style="color: #666; font-size: 0.9rem;">${product.category || ''}</p>
                        <a class="btn" href="views/product_detail.php?product_id=${product.product_id}">查看詳情</a>
                    `;
                    container.appendChild(card);
                });
            } catch (error) {
                console.error('載入商品失敗:', error);
                document.getElementById('featuredProducts').innerHTML = 
                    '<p style="text-align: center; padding: 40px; color: #d14343;">載入商品時發生錯誤</p>';
            }
        }

        // 頁面載入時執行
        document.addEventListener('DOMContentLoaded', loadFeaturedProducts);
    </script>
</body>
</html>

