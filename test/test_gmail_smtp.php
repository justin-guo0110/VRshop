<?php
// 測試 PHPMailer 和 Gmail SMTP
require_once __DIR__ . '/lib/mailer.php';

echo "測試 Gmail SMTP 連線...\n\n";

$testResult = send_mail(
    'justinguo0110@gmail.com',
    'VR Mall 測試信件',
    '<h2>這是一封測試信件</h2><p>如果你收到這份信，表示 SMTP 設定成功！</p>',
    '如果你收到這份信，表示 SMTP 設定成功！'
);

if ($testResult['success']) {
    echo "✅ 成功！信件已寄出\n";
    echo "訊息: " . $testResult['message'] . "\n";
} else {
    echo "❌ 失敗\n";
    echo "錯誤: " . $testResult['message'] . "\n";
}
?>
