<?php require_once __DIR__ . '/layout_header.php'; ?>
<section class="card">
    <div class="tabs">
        <button class="tab-button active" data-target="login-form">Login</button>
        <button class="tab-button" data-target="register-form">Register</button>
    </div>
    <div id="login-form" class="tab-content active">
        <form id="loginForm">
            <label>Email</label>
            <input type="email" name="email" required>
            <label>Password</label>
            <input type="password" name="password" required>
            <button type="submit" class="btn">Login</button>
        </form>
    </div>
    <div id="register-form" class="tab-content">
        <form id="registerForm">
            <label>Email</label>
            <input type="email" name="email" required>
            <label>Password</label>
            <input type="password" name="password" required>
            <label>Name</label>
            <input type="text" name="name" required>
            <label>Phone</label>
            <input type="text" name="phone">
            <button type="submit" class="btn">Register</button>
        </form>
    </div>
    <div class="message" id="authMessage"></div>
</section>
<?php require_once __DIR__ . '/layout_footer.php'; ?>
