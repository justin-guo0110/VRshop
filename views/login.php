<?php require_once __DIR__ . '/layout_header.php'; ?>
<div class="login-wrapper">
    <section class="login-card">
        <div class="login-header">
            <h1>VR Mall</h1>
            <p>登入您的帳號</p>
        </div>

        <div class="login-tabs">
            <button class="login-tab active" data-target="login-form">登入</button>
            <button class="login-tab" data-target="register-form">建立帳號</button>
        </div>

        <div id="login-form" class="login-content active">
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
            <div class="login-footer">
                <a href="javascript:void(0);" onclick="switchTab('forgot-form'); return false;">忘記密碼？</a>
            </div>
        </div>

        <div id="register-form" class="login-content">
            <form id="registerForm">
                <div class="form-group">
                    <label>電子郵件</label>
                    <input type="email" name="email" placeholder="你的電子郵件" required>
                </div>
                <div class="form-group">
                    <label>密碼</label>
                    <input type="password" name="password" placeholder="至少 8 個字元" required minlength="8">
                </div>
                <div class="form-group">
                    <label>姓名</label>
                    <input type="text" name="name" placeholder="全名" required>
                </div>
                <div class="form-group">
                    <label>電話號碼</label>
                    <input type="text" name="phone" placeholder="(選填)">
                </div>
                <button type="submit" class="btn btn-login">建立帳號</button>
                <div class="message" id="registerMessage"></div>
            </form>
        </div>

        <div id="forgot-form" class="login-content">
            <button class="back-btn" onclick="switchTab('login-form'); return false;">← 回到登入</button>
            <form id="forgotPasswordForm">
                <div class="form-group">
                    <label>註冊電子郵件</label>
                    <input type="email" name="email" placeholder="輸入你的電子郵件" required>
                </div>
                <button type="submit" class="btn btn-login">寄送重設連結</button>
                <div class="message" id="forgotPasswordMessage"></div>
            </form>
        </div>
    </section>
</div>
<?php require_once __DIR__ . '/layout_footer.php'; ?>
