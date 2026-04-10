<?php
$token = $_GET['token'] ?? '';
require_once __DIR__ . '/layout_header.php';
?>
<section class="card">
    <h2>重設密碼</h2>
    <div id="tokenStatus" style="text-align: center; padding: 20px;">
        <p>驗證中...</p>
    </div>
    <form id="resetPasswordForm" style="display: none;">
        <input type="hidden" name="token" value="<?php echo htmlspecialchars($token, ENT_QUOTES); ?>">
        <label>新密碼（至少 8 個字元）</label>
        <input type="password" name="password" id="passwordInput" required minlength="8" placeholder="請輸入至少 8 個字元的密碼">
        <small style="color: #666; display: block; margin-top: 4px;">密碼長度至少需要 8 個字元</small>
        <label>確認新密碼</label>
        <input type="password" name="confirm_password" id="confirmPasswordInput" required minlength="8" placeholder="請再次輸入密碼">
        <div id="passwordMatch" style="margin-top: 4px; font-size: 0.9rem;"></div>
        <button type="submit" class="btn" style="margin-top: 16px;">更新密碼</button>
    </form>
    <div class="message" id="resetPasswordMessage"></div>
</section>
<?php require_once __DIR__ . '/layout_footer.php'; ?>

<script>
// 驗證 token 並載入表單
async function verifyToken() {
    const token = '<?php echo htmlspecialchars($token, ENT_QUOTES); ?>';
    const statusDiv = document.getElementById('tokenStatus');
    const form = document.getElementById('resetPasswordForm');
    
    if (!token) {
        statusDiv.innerHTML = '<p style="color: #d14343;">連結有誤，請重新申請忘記密碼。</p><a href="login.php" class="btn">返回登入</a>';
        return;
    }
    
    try {
        const response = await fetch('../api/auth.php?action=verify_reset_token&token=' + encodeURIComponent(token));
        const data = await response.json();
        
        if (data.valid) {
            statusDiv.style.display = 'none';
            form.style.display = 'block';
        } else {
            statusDiv.innerHTML = `
                <p style="color: #d14343;">${data.error || '連結已失效'}</p>
                <p style="margin-top: 12px;">請重新申請忘記密碼。</p>
                <a href="login.php" class="btn" style="margin-top: 12px;">返回登入</a>
            `;
        }
    } catch (error) {
        statusDiv.innerHTML = '<p style="color: #d14343;">驗證時發生錯誤，請稍後再試。</p><a href="login.php" class="btn">返回登入</a>';
    }
}

// 密碼匹配檢查
function checkPasswordMatch() {
    const password = document.getElementById('passwordInput').value;
    const confirm = document.getElementById('confirmPasswordInput').value;
    const matchDiv = document.getElementById('passwordMatch');
    
    if (confirm === '') {
        matchDiv.textContent = '';
        return;
    }
    
    if (password === confirm) {
        matchDiv.textContent = '✓ 密碼匹配';
        matchDiv.style.color = '#0f9d58';
    } else {
        matchDiv.textContent = '✗ 密碼不匹配';
        matchDiv.style.color = '#d14343';
    }
}

// 頁面載入時驗證 token
document.addEventListener('DOMContentLoaded', () => {
    verifyToken();
    
    // 監聽密碼輸入
    const passwordInput = document.getElementById('passwordInput');
    const confirmInput = document.getElementById('confirmPasswordInput');
    
    if (passwordInput && confirmInput) {
        confirmInput.addEventListener('input', checkPasswordMatch);
        passwordInput.addEventListener('input', () => {
            if (confirmInput.value) {
                checkPasswordMatch();
            }
        });
    }
});
</script>

