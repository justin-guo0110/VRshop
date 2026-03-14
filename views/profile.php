<?php require_once __DIR__ . '/layout_header.php'; ?>
<?php if (!$currentUser): ?>
    <div class="card">
        <p>請 <a href="login.php">登入</a> 以管理您的個人資料。</p>
    </div>
<?php else: ?>
    <section class="grid two-cols">
        <div class="card">
            <h2>個人簡介</h2>
            <form id="profileForm">
                <label>電子郵件</label>
                <input type="email" value="<?php echo htmlspecialchars($currentUser['email']); ?>" disabled>
                <label>姓名</label>
                <input type="text" name="name" value="<?php echo htmlspecialchars($currentUser['name'] ?? ''); ?>" required>
                <label>電話號碼</label>
                <input type="text" name="phone" value="<?php echo htmlspecialchars($currentUser['phone'] ?? ''); ?>">
                <button type="submit" class="btn">儲存</button>
            </form>
            <div class="message" id="profileMessage"></div>
        </div>
        <div class="card" id="addressSection">
            <h2>地址</h2>
            <div id="addressList"></div>
            <form id="addressForm">
                <label>收件人姓名</label>
                <input type="text" name="recipient_name" required>
                <label>電話號碼</label>
                <input type="text" name="phone">
                <label>寄送地址</label>
                <input type="text" name="address_line" required>
                <button type="submit" class="btn">儲存</button>
            </form>
            <div class="message" id="addressMessage"></div>
        </div>
    </section>
<?php endif; ?>
<?php require_once __DIR__ . '/layout_footer.php'; ?>
