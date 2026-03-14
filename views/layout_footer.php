    </main>
    <footer class="site-footer">
        <div class="container">
            <p>© <?php echo date('Y'); ?> VR Mall</p>
        </div>
    </footer>
    <?php if (empty($disableChatWidget)): ?>
        <link rel="stylesheet" href="../public/css/chat.css">
    <?php endif; ?>
    <script src="../public/js/app.js"></script>
    <?php if (empty($disableChatWidget)): ?>
        <script src="../public/js/chat.js"></script>
    <?php endif; ?>
</body>
</html>
