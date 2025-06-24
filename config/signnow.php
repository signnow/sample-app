<?php

return [
    'api' => [
        'host' => env('SIGNNOW_API_HOST', 'https://api.signnow.com'),
        'basic_token' => env('SIGNNOW_API_BASIC_TOKEN'),
        'user' => env('SIGNNOW_API_USERNAME'),
        'password' => env('SIGNNOW_API_PASSWORD'),
        'signer_role' => 'Customer',
        'signer_email' => env('SIGNNOW_SIGNER_EMAIL'),
        'redirect_url' => env('APP_URL') . '/thank-you',
    ],
];
