<?php require_once __DIR__ . '/layout_header.php'; ?>
<section class="card">
    <div class="tabs">
        <button class="tab-button" data-target="register-form">註冊</button>
        <button class="tab-button active" data-target="login-form">登入</button>
        <button class="tab-button" data-target="forgot-form">忘記密碼</button>
    </div>
    <div id="login-form" class="tab-content active">
        <form id="loginForm">
            <label>電子郵件</label>
            <input type="email" name="email" required>
            <label>密碼</label>
            <input type="password" name="password" required>
            <button type="submit" class="btn">登入</button>
        </form>
    </div>
    <div id="register-form" class="tab-content">
        <form id="registerForm">
            <label>電子郵件</label>
            <input type="email" name="email" required>
            <label>密碼（至少 8 個字符）</label>
            <input type="password" name="password" required minlength="8" placeholder="請輸入至少 8 個字符的密碼">
            <small style="color: #666; display: block; margin-top: 4px;">密碼長度至少需要 8 個字符</small>
            <label>姓名</label>
            <input type="text" name="name" required>
            <label>電話號碼</label>
            <input type="text" name="phone">
            <button type="submit" class="btn">註冊</button>
        </form>
    </div>
    <div id="forgot-form" class="tab-content">
        <form id="forgotPasswordForm">
            <label>註冊電子郵件</label>
            <input type="email" name="email" required>
            <button type="submit" class="btn">寄送重設連結</button>
        </form>
        <div class="message" id="forgotPasswordMessage"></div>
    </div>
    <div class="message" id="authMessage"></div>
</section>
<?php require_once __DIR__ . '/layout_footer.php'; ?>
