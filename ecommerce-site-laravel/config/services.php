<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
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

    'fcm' => [
    'server_key' => env('FCM_SERVER_KEY'),
    ],

    'mtn' => [
    'api_url' => env('MTN_API_URL', 'https://sandbox.momodeveloper.mtn.com'),
    'api_key' => env('MTN_API_KEY'),
    'api_secret' => env('MTN_API_SECRET'),
    'environment' => env('MTN_ENVIRONMENT', 'sandbox'),
],

'orange' => [
    'api_url' => env('ORANGE_API_URL'),
    'merchant_id' => env('ORANGE_MERCHANT_ID'),
    'api_key' => env('ORANGE_API_KEY'),
],

];
