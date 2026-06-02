<?php

return [

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    // Payment gateways (§6.5). Credentials supplied per environment.
    'knet' => [
        'tranportal_id' => env('KNET_TRANPORTAL_ID'),
        'tranportal_password' => env('KNET_TRANPORTAL_PASSWORD'),
        'terminal_resource_key' => env('KNET_RESOURCE_KEY'),
        'base_url' => env('KNET_BASE_URL'),
    ],

    'myfatoorah' => [
        'token' => env('MYFATOORAH_TOKEN'),
        'base_url' => env('MYFATOORAH_BASE_URL', 'https://apitest.myfatoorah.com'),
    ],

    // Messaging (§6.7).
    'whatsapp' => [
        'token' => env('WHATSAPP_TOKEN'),
        'phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID'),
    ],

    'fcm' => [
        'credentials' => env('FCM_CREDENTIALS_PATH'),
    ],

];
