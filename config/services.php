<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
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

    'sms' => [
        'driver' => env('SMS_DRIVER', 'log'),
        'log_channel' => env('SMS_LOG_CHANNEL', 'stack'),
    ],

    // Safaricom Daraja (M-Pesa STK Push / B2C). Integrated in the payments phase.
    'mpesa' => [
        'environment' => env('MPESA_ENVIRONMENT', 'sandbox'),
        'consumer_key' => env('MPESA_CONSUMER_KEY'),
        'consumer_secret' => env('MPESA_CONSUMER_SECRET'),
        'shortcode' => env('MPESA_SHORTCODE'),
        'passkey' => env('MPESA_PASSKEY'),
        'callback_url' => env('MPESA_CALLBACK_URL'),
        'callback_secret' => env('MPESA_CALLBACK_SECRET'),
    ],

    'payment_provider' => [
        'key' => env('PAYMENT_PROVIDER_KEY'),
        'secret' => env('PAYMENT_PROVIDER_SECRET'),
        'webhook_secret' => env('PAYMENT_PROVIDER_WEBHOOK_SECRET'),
    ],

    // "Sign in with Google" (Socialite). The button only shows once a client ID is set.
    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI', '/auth/google/callback'),
    ],

    // In-app AI assistant (Claude). The chat widget only shows once an API key is set.
    'anthropic' => [
        'api_key' => env('ANTHROPIC_API_KEY'),
        'model' => env('ASSISTANT_MODEL', 'claude-opus-5'),
    ],

    'market_data' => [
        'driver' => env('MARKET_DATA_DRIVER', 'sandbox'),
        'api_key' => env('MARKET_DATA_API_KEY'),
        // Prices older than this are shown as delayed.
        'stale_after_seconds' => (int) env('MARKET_DATA_STALE_AFTER_SECONDS', 300),
        'binance' => [
            // https://data-api.binance.vision serves the same public data where api.binance.com is geo-blocked.
            'base_url' => env('BINANCE_BASE_URL', 'https://api.binance.com'),
            // Binance quotes crypto in USDT (a USD stablecoin); USD markets read the USDT pairs.
            'quote_aliases' => ['USD' => 'USDT'],
        ],
    ],

    'trading' => [
        'driver' => env('TRADING_DRIVER', 'sandbox'),
        'api_key' => env('TRADING_API_KEY'),
        'api_secret' => env('TRADING_API_SECRET'),
        'webhook_secret' => env('TRADING_WEBHOOK_SECRET'),
    ],

];
