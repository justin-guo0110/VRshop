<?php require_once __DIR__ . '/layout_header.php'; ?>

<div class="profile-page">
    <?php if (!$currentUser): ?>
        <div class="card">
            <p>請 <a href="login.php">登入</a> 以管理您的個人資料。</p>
        </div>
    <?php else: ?>
        <section class="profile-sections">
            <div class="card">
                <h2>個人簡介</h2>
                <form id="profileForm">
                    <label>電子郵件</label>
                    <input type="email" value="<?php echo htmlspecialchars($currentUser['email']); ?>" disabled>
                    <label>姓名</label>
                    <input type="text" name="name" value="<?php echo htmlspecialchars($currentUser['name'] ?? ''); ?>"
                        required>
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

            <div class="card" id="cardSection">
                <h2>付款方式</h2>

                <div id="cardList"></div>

                <form id="cardForm">
                    <label>持卡人姓名</label>
                    <input type="text" name="card_holder" placeholder="例如：CHEN DA MING" required>

                    <label>卡號</label>
                    <input type="text" name="card_number" placeholder="0000 0000 0000 0000" maxlength="19"
                        inputmode="numeric" autocomplete="off" required>

                    <label>有效期限</label>
                    <input type="text" name="expiry" placeholder="MM/YY" maxlength="5" inputmode="numeric"
                        autocomplete="off" required>
                    <label>安全碼</label>
                    <input type="password" name="cvv" placeholder="CVV" maxlength="4" inputmode="numeric" autocomplete="off"
                        required>

                    <label class="card-save-option">

                        <input type="checkbox" name="is_default" value="1">

                        <span style="color:#111827; font-weight:700;">
                            設為預設付款卡
                        </span>

                    </label>

                    <button type="submit" class="btn">新增卡片</button>
                </form>

                <div class="message" id="cardMessage"></div>
            </div>
        </section>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/layout_footer.php'; ?>