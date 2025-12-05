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
    'transport' => 'log',

    'from_email' => 'no-reply@example.com',
    'from_name'  => 'VR Mall Support',

    'smtp' => [
        'host'       => 'smtp.example.com',
        'port'       => 587,
        'username'   => 'your-account@example.com',
        'password'   => 'app-password',
        'encryption' => 'tls', // 支援 tls 或 ssl
        'timeout'    => 10,
    ],

    // 當 transport 設為 log 時，郵件會附加在此檔案
    'log_path' => __DIR__ . '/../storage/mail.log',
];

