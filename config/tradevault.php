<?php

/*
|--------------------------------------------------------------------------
| Platform & compliance configuration
|--------------------------------------------------------------------------
|
| Business rules that must be reviewed by the operator (and their legal /
| compliance advisers) before handling real customer funds. Nothing in this
| file makes the business licensed to hold deposits or offer investments.
|
*/

return [

    /*
     | When true, the UI shows a persistent DEMO / SANDBOX banner and all
     | market data is simulated. Must be false only once a licensed
     | market-data / execution provider is configured.
     */
    'demo_mode' => env('PLATFORM_DEMO_MODE', true),

    'base_currency' => env('PLATFORM_BASE_CURRENCY', 'USD'),

    'currencies' => array_filter(explode(',', env('PLATFORM_CURRENCIES', 'USD,KES'))),

    'registration' => [
        'enabled' => env('REGISTRATION_ENABLED', true),
        'minimum_age' => (int) env('REGISTRATION_MINIMUM_AGE', 18),
    ],

    /*
     | ISO 3166-1 alpha-2 codes. If `allowed_countries` is non-empty only those
     | may register; `blocked_countries` is always enforced.
     */
    'geo' => [
        'allowed_countries' => array_filter(explode(',', env('GEO_ALLOWED_COUNTRIES', ''))),
        'blocked_countries' => array_filter(explode(',', env('GEO_BLOCKED_COUNTRIES', ''))),
    ],

    'kyc' => [
        'required_for_withdrawal' => env('KYC_REQUIRED_FOR_WITHDRAWAL', true),
        'required_for_trading' => env('KYC_REQUIRED_FOR_TRADING', false),
        'documents_disk' => env('KYC_DOCUMENTS_DISK', 'kyc'),
        'max_upload_kb' => (int) env('KYC_MAX_UPLOAD_KB', 8192),
    ],

    /*
     | Limits are shown to users *before* they submit a request.
     */
    'limits' => [
        'deposit_min' => env('LIMIT_DEPOSIT_MIN', '10'),
        'deposit_max' => env('LIMIT_DEPOSIT_MAX', '50000'),
        'withdrawal_min' => env('LIMIT_WITHDRAWAL_MIN', '10'),
        'withdrawal_max' => env('LIMIT_WITHDRAWAL_MAX', '50000'),
        'withdrawal_daily_max' => env('LIMIT_WITHDRAWAL_DAILY_MAX', '100000'),
    ],

    /*
     | Versioned legal documents. Content lives in resources/legal/{key}.md
     | and MUST be replaced with counsel-reviewed text before launch.
     */
    'legal' => [
        'terms_version' => env('LEGAL_TERMS_VERSION', '2026-09-01'),
        'documents' => [
            'terms' => 'Terms and Conditions',
            'privacy' => 'Privacy Policy',
            'risk' => 'Risk Disclosure',
            'aml' => 'KYC / AML Policy',
            'withdrawals' => 'Withdrawal Policy',
        ],
    ],

    'support_email' => env('SUPPORT_EMAIL', 'support@example.com'),
];
