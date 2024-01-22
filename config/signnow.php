<?php

return [
    'api' => [
        'host' => env('SN_API_HOST', 'https://api.signnow.com'),
        'basic_token' => env('SN_API_BASIC_TOKEN'),
        'user' => env('SN_API_USER'),
        'password' => env('SN_API_PASSWORD'),
        'signer_role' => 'Customer',
        'signer_email' => env('SN_SIGNER_EMAIL'),
        'redirect_url' => env('APP_URL') . '/thank-you',
    ],
];
