<?php

/**
 * Generic mail helper that supports log/mail/smtp transports.
 */
function mail_config(): array
{
    static $config = null;
    if ($config !== null) {
        return $config;
    }

    $default = [
        'transport'  => 'log',
        'from_email' => 'no-reply@example.com',
        'from_name'  => 'VR Mall',
        'log_path'   => __DIR__ . '/../storage/mail.log',
        'smtp'       => [
            'host'       => 'localhost',
            'port'       => 587,
            'username'   => '',
            'password'   => '',
            'encryption' => 'tls',
            'timeout'    => 10,
        ],
    ];

    $path = __DIR__ . '/../config/mail.php';
    if (file_exists($path)) {
        $loaded = require $path;
        $config = array_merge($default, $loaded);
        $config['smtp'] = array_merge($default['smtp'], $config['smtp'] ?? []);
    } else {
        $config = $default;
    }

    return $config;
}

/**
 * Send mail based on the configured transport.
 */
function send_mail(string $to, string $subject, string $htmlBody, ?string $textBody = null): array
{
    $config = mail_config();
    $transport = strtolower($config['transport'] ?? 'log');

    if ($transport === 'smtp') {
        $result = send_via_smtp($config, $to, $subject, $htmlBody, $textBody);
        if ($result['success']) {
            return $result;
        }
        // 如果 SMTP 失敗，寫入 log 以免流程中斷
        log_mail($config, $to, $subject, $htmlBody, $textBody, $result['message']);
        return ['success' => false, 'message' => $result['message']];
    }

    if ($transport === 'mail') {
        $result = send_via_mail_function($config, $to, $subject, $htmlBody, $textBody);
        if ($result['success']) {
            return $result;
        }
        log_mail($config, $to, $subject, $htmlBody, $textBody, $result['message']);
        return ['success' => false, 'message' => $result['message']];
    }

    log_mail($config, $to, $subject, $htmlBody, $textBody);
    return ['success' => true, 'message' => 'logged'];
}

function send_via_mail_function(array $config, string $to, string $subject, string $html, ?string $text): array
{
    $headers = [];
    $headers[] = 'MIME-Version: 1.0';
    $headers[] = 'Content-type: text/html; charset=UTF-8';
    $headers[] = 'From: ' . format_from($config);

    $success = @mail($to, encode_subject($subject), $html, implode("\r\n", $headers));
    return $success
        ? ['success' => true, 'message' => 'sent_via_mail']
        : ['success' => false, 'message' => 'mail() failed'];
}

function send_via_smtp(array $config, string $to, string $subject, string $html, ?string $text): array
{
    $autoload = __DIR__ . '/../vendor/autoload.php';
    if (file_exists($autoload)) {
        require_once $autoload;
    }

    if (!class_exists(\PHPMailer\PHPMailer\PHPMailer::class)) {
        return ['success' => false, 'message' => 'PHPMailer not installed (composer require phpmailer/phpmailer)'];
    }

    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = $config['smtp']['host'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $config['smtp']['username'];
        $mail->Password   = $config['smtp']['password'];
        $mail->SMTPSecure = $config['smtp']['encryption'];
        $mail->Port       = (int) $config['smtp']['port'];
        $mail->Timeout    = (int) $config['smtp']['timeout'];
        $mail->CharSet    = 'UTF-8';

        $mail->setFrom($config['from_email'], $config['from_name']);
        $mail->addAddress($to);
        $mail->Subject = $subject;
        $mail->isHTML(true);
        $mail->Body = $html;
        $mail->AltBody = $text ?? strip_tags($html);
        $mail->send();

        return ['success' => true, 'message' => 'sent_via_smtp'];
    } catch (\Throwable $e) {
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

function log_mail(array $config, string $to, string $subject, string $html, ?string $text, string $reason = ''): void
{
    $path = $config['log_path'] ?? (__DIR__ . '/../storage/mail.log');
    $dir = dirname($path);
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
    $timestamp = date('Y-m-d H:i:s');
    $payload = "[$timestamp]\nTo: $to\nSubject: $subject\nReason: " . ($reason ?: 'log transport') . "\nHTML:\n$html\nText:\n" . ($text ?? strip_tags($html)) . "\n-----------------\n";
    file_put_contents($path, $payload, FILE_APPEND);
}

function format_from(array $config): string
{
    $name = trim($config['from_name'] ?? '');
    $email = trim($config['from_email'] ?? '');
    return $name ? sprintf('"%s" <%s>', addslashes($name), $email) : $email;
}

function encode_subject(string $subject): string
{
    return '=?UTF-8?B?' . base64_encode($subject) . '?=';
}


