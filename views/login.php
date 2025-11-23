<?php require_once __DIR__ . '/layout_header.php'; ?>
<section class="card">
    <div class="tabs">
        <button class="tab-button" data-target="register-form">註冊</button>
        <button class="tab-button active" data-target="login-form">登入</button>
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
            <label>密碼</label>
            <input type="password" name="password" required>
            <label>姓名</label>
            <input type="text" name="name" required>
            <label>電話號碼</label>
            <input type="text" name="phone">
            <button type="submit" class="btn">註冊</button>
        </form>
    </div>
    <div class="message" id="authMessage"></div>
</section>
<?php require_once __DIR__ . '/layout_footer.php'; ?>
