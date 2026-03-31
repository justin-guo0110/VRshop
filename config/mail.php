<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Transport Driver
    |--------------------------------------------------------------------------
    | Supported: "log", "mail", "smtp"
    | - log:   不實際寄信，將郵件內容寫入 storage/mail.log，方便開發測試
    | - mail:  使用 PHP mail()，需另外在 php.ini 設定 SMTP
    | - smtp:  使用 PHPMailer 透過 SMTP 寄送，需先在專案根目錄執行
    |          composer require phpmailer/phpmailer
    */
    'transport' => getenv('MAIL_TRANSPORT') ?: 'smtp',

    'from_email' => getenv('MAIL_FROM_EMAIL') ?: 'noreply@example.com',
    'from_name'  => getenv('MAIL_FROM_NAME') ?: 'VR Mall Support',

    'smtp' => [
        'host'       => getenv('MAIL_HOST') ?: 'smtp.gmail.com',
        'port'       => intval(getenv('MAIL_PORT') ?: '587'),
        'username'   => getenv('MAIL_USERNAME') ?: '',
        'password'   => getenv('MAIL_PASSWORD') ?: '',
        'encryption' => getenv('MAIL_ENCRYPTION') ?: 'tls',
        'timeout'    => intval(getenv('MAIL_TIMEOUT') ?: '10'),
    ],

    // 當 transport 設為 log 時，郵件會附加在此檔案
    'log_path' => __DIR__ . '/../storage/mail.log',
];

