<?php
require_once __DIR__ . '/vendor/autoload.php';

$config = require __DIR__ . '/config/mail.php';

echo "=== SMTP 連接測試 ===\n";
echo "Host: " . $config['smtp']['host'] . "\n";
echo "Port: " . $config['smtp']['port'] . "\n";
echo "Username: " . $config['smtp']['username'] . "\n";
echo "Encryption: " . $config['smtp']['encryption'] . "\n\n";

$mail = new \PHPMailer\PHPMailer\PHPMailer(true);

try {
    $mail->isSMTP();
    $mail->Host = $config['smtp']['host'];
    $mail->SMTPAuth = true;
    $mail->Username = $config['smtp']['username'];
    $mail->Password = $config['smtp']['password'];
    $mail->SMTPSecure = $config['smtp']['encryption'];
    $mail->Port = $config['smtp']['port'];
    $mail->Timeout = $config['smtp']['timeout'];
    $mail->CharSet = 'UTF-8';

    echo "✅ SMTP 連接成功！\n\n";
    
    // 測試寄送
    $mail->setFrom($config['from_email'], $config['from_name']);
    $mail->addAddress('justinguo0110@gmail.com');
    $mail->Subject = '測試郵件';
    $mail->Body = '這是一封測試郵件，來自 VR Mall 系統。';
    $mail->AltBody = '這是一封測試郵件，來自 VR Mall 系統。';

    if ($mail->send()) {
        echo "✅ 測試郵件已寄出！\n";
    } else {
        echo "❌ 郵件寄送失敗：" . $mail->ErrorInfo . "\n";
    }
} catch (Exception $e) {
    echo "❌ SMTP 連接失敗！\n";
    echo "錯誤信息：" . $e->getMessage() . "\n";
}
?>
