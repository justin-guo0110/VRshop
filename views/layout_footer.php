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
    <?php if (empty($disableChatWidget)): ?>
        <link rel="stylesheet" href="../public/css/chat.css">
    <?php endif; ?>
    <script src="../public/js/app.js?v=<?php echo filemtime(__DIR__ . '/../public/js/app.js'); ?>"></script>
    <?php if (empty($disableChatWidget)): ?>
        <script src="../public/js/chat.js"></script>
    <?php endif; ?>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
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
