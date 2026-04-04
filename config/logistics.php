<?php
return [
    'ecpay' => [
        // Set APP_ENV=production for production endpoint and credentials.
        'env' => getenv('APP_ENV') ?: 'sandbox',
        // Keep empty by default. Configure real credentials in environment variables.
        'merchant_id' => getenv('ECPAY_MERCHANT_ID') ?: '',
        'hash_key' => getenv('ECPAY_HASH_KEY') ?: '',
        'hash_iv' => getenv('ECPAY_HASH_IV') ?: '',
    ],
];
