<?php require_once __DIR__ . '/layout_header.php'; ?>

<div class="login-wrapper">
    <section class="accordion-container">
        <div class="login-header" style="text-align: center; padding: 20px 0;">
            <h1 style="margin: 0; color: var(--primary);">VR Mall</h1>
            <p style="margin: 5px 0 0; color: var(--muted);">歡迎來到您的虛擬購物天堂</p>
        </div>

        <div class="accordion-item active" id="item-login">
            <div class="accordion-header" onclick="toggleAccordion('item-login')">
                <span>登入帳號</span>
                <i class="arrow"></i>
            </div>
            <div class="accordion-content">
                <form id="loginForm">
                    <div class="form-group">
                        <label>電子郵件</label>
                        <input type="email" name="email" placeholder="你的電子郵件" required>
                    </div>
                    <div class="form-group">
                        <label>密碼</label>
                        <input type="password" name="password" placeholder="密碼" required>
                    </div>
                    <button type="submit" class="btn btn-login">登入</button>
                    <div class="message" id="authMessage"></div>
                </form>
            </div>
        </div>

        <div class="accordion-item" id="item-register">
            <div class="accordion-header" onclick="toggleAccordion('item-register')">
                <span>建立帳號</span>
                <i class="arrow"></i>
            </div>
            <div class="accordion-content">
                <form id="registerForm">
                    <div class="form-group">
                        <label>電子郵件</label>
                        <input type="email" name="email" placeholder="例如：user@example.com" required>
                    </div>
                    <div class="form-group">
                        <label>密碼</label>
                        <input type="password" name="password" placeholder="至少 8 個字元" required minlength="8">
                    </div>
                    <div class="form-group">
                        <label>姓名</label>
                        <input type="text" name="name" placeholder="您的全名" required>
                    </div>
                    <div class="form-group">
                        <label>電話號碼</label>
                        <input type="text" name="phone" placeholder="(選填)">
                    </div>
                    <button type="submit" class="btn btn-login">建立帳號</button>
                    <div class="message" id="registerMessage"></div>
                </form>
            </div>
        </div>

        <div class="accordion-item" id="item-forgot">
            <div class="accordion-header" onclick="toggleAccordion('item-forgot')">
                <span>忘記密碼</span>
                <i class="arrow"></i>
            </div>
            <div class="accordion-content">
                <form id="forgotPasswordForm">
                    <div class="form-group" style="margin-top: 10px;">
                        <p style="font-size: 0.9rem; color: #666; margin-bottom: 15px;">
                            請輸入您註冊時使用的電子郵件，我們將寄送密碼重設連結給您。
                        </p>
                        <label>註冊電子郵件</label>
                        <input type="email" name="email" placeholder="輸入你的電子郵件" required>
                    </div>
                    <button type="submit" class="btn btn-login">寄送重設連結</button>
                    <div class="message" id="forgotPasswordMessage"></div>
                </form>
            </div>
        </div>
    </section>
</div>

<script>
/**
 * 切換手風琴區塊展開/收合
 * @param {string} itemId 欲展開的區塊 ID
 */
function toggleAccordion(itemId) {
    const items = document.querySelectorAll('.accordion-item');
    items.forEach(item => {
        if (item.id === itemId) {
            // 如果點擊的是當前區塊，通常維持展開或不做動作 (手風琴邏輯)
            item.classList.add('active');
        } else {
            // 關閉其他所有區塊
            item.classList.remove('active');
        }
    });
}

/**
 * 為了保持與 app.js 的相容性
 * 如果原本 app.js 有 switchTab 函數，我們將其重新映射至手風琴邏輯
 */
window.switchTab = function(target) {
    const mapping = {
        'login-form': 'item-login',
        'register-form': 'item-register',
        'forgot-form': 'item-forgot'
    };
    const accordionId = mapping[target] || target;
    toggleAccordion(accordionId);
};
</script>

<?php require_once __DIR__ . '/layout_footer.php'; ?>

<?php require_once __DIR__ . '/layout_header.php'; ?>