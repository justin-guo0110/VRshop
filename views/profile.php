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
    <section style="margin-top:16px;">
        <div class="card">
            <h2>我的優惠券</h2>
            <div id="couponList"></div>
            <div class="message" id="couponMessage"></div>
        </div>
    </section>
    <script>
        document.addEventListener('DOMContentLoaded', async () => {
            const list = document.getElementById('couponList');
            const msg = document.getElementById('couponMessage');
            if (!list || !msg || typeof api === 'undefined') return;

            try {
                const res = await api.get('../api/lucky_wheel.php?action=list_coupons&only_active=1');
                const coupons = res.coupons || [];
                if (!coupons.length) {
                    list.innerHTML = '<p style="margin:0;color:#64748b;">目前沒有可用優惠券，登入後可參加轉盤活動。</p>';
                    return;
                }

                list.innerHTML = coupons.map(c => {
                    const discountText = c.discount_type === 'percent'
                        ? `${Number(c.discount_value)}%`
                        : `$${Number(c.discount_value).toFixed(0)}`;
                    return `
                        <div style="border:1px solid #e2e8f0;border-radius:10px;padding:10px 12px;margin-bottom:10px;background:#fff;">
                            <div style="display:flex;justify-content:space-between;gap:8px;align-items:center;">
                                <strong>${c.description || '優惠券'}</strong>
                                <span style="font-weight:700;color:#0f766e;">${discountText}</span>
                            </div>
                            <div style="margin-top:6px;color:#334155;font-size:14px;">代碼：<strong>${c.coupon_code}</strong></div>
                            <div style="margin-top:4px;color:#64748b;font-size:13px;">最低消費：$${Number(c.min_purchase).toFixed(0)} ｜ 到期：${c.expiry_date}</div>
                        </div>
                    `;
                }).join('');
            } catch (err) {
                msg.textContent = '優惠券載入失敗，請稍後再試';
                msg.className = 'message error';
            }
        });
    </script>
<?php endif; ?>
</div>

<?php require_once __DIR__ . '/layout_footer.php'; ?>