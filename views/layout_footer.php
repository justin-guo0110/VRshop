    </main>
    <footer class="site-footer">
        <div class="container">
            <div style="display:flex;gap:14px;flex-wrap:wrap;justify-content:center;margin-bottom:8px;font-weight:700;">
                <a href="./shipping.php">運送說明</a>
                <a href="./returns.php">退換貨政策</a>
                <a href="./privacy.php">隱私權政策</a>
                <a href="./contact.php">聯絡我們</a>
            </div>
            <p style="margin:0;">© <?php echo date('Y'); ?> VR Mall</p>
        </div>
    </footer>
    <?php if (empty($disableChatWidget)): ?>
        <link rel="stylesheet" href="../public/css/chat.css">
    <?php endif; ?>
    <script src="../public/js/app.js?v=<?php echo filemtime(__DIR__ . '/../public/js/app.js'); ?>"></script>
    <?php if (empty($disableChatWidget)): ?>
        <script src="../public/js/chat.js"></script>
    <?php endif; ?>
</body>
</html>
