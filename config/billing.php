<?php

// Credits pay for AI work: a kit (CV and letter for one role), a resume translation or a resume read costs one credit.
// Exports, themes, downloads and everything else stay free. Set BILLING_ENABLED=false to make AI free again.
return [
    'enabled' => (bool) env('BILLING_ENABLED', true),
    'signup_credits' => (int) env('BILLING_SIGNUP_CREDITS', 20),
    // Added on the 1st of each month to every candidate with a verified email (credits:monthly).
    'monthly_credits' => (int) env('BILLING_MONTHLY_CREDITS', 5),
    // Each credit covers the first draft of a kit or a resume translation plus this many rewrites.
    'rewrites_per_kit' => (int) env('BILLING_REWRITES_PER_KIT', 3),

    // Prices are set by the owner before billing is turned on. Amounts in the smallest unit (millimes for TND, cents for USD).
    'packs' => [
        'starter' => ['credits' => 20, 'price_tnd' => env('BILLING_STARTER_TND'), 'price_usd' => env('BILLING_STARTER_USD'), 'lemonsqueezy_variant' => env('LEMONSQUEEZY_VARIANT_STARTER')],
        'standard' => ['credits' => 60, 'price_tnd' => env('BILLING_STANDARD_TND'), 'price_usd' => env('BILLING_STANDARD_USD'), 'lemonsqueezy_variant' => env('LEMONSQUEEZY_VARIANT_STANDARD')],
        'pro' => ['credits' => 150, 'price_tnd' => env('BILLING_PRO_TND'), 'price_usd' => env('BILLING_PRO_USD'), 'lemonsqueezy_variant' => env('LEMONSQUEEZY_VARIANT_PRO')],
    ],

    'gateways' => [
        'konnect' => [
            'currency' => 'TND',
            'api_key' => env('KONNECT_API_KEY'),
            'wallet_id' => env('KONNECT_WALLET_ID'),
            'base_url' => env('KONNECT_BASE_URL', 'https://api.sandbox.konnect.network/api/v2'),
        ],
        'flouci' => [
            'currency' => 'TND',
            'public_key' => env('FLOUCI_PUBLIC_KEY'),
            'private_key' => env('FLOUCI_PRIVATE_KEY'),
            'base_url' => env('FLOUCI_BASE_URL', 'https://developers.flouci.com/api/v2'),
        ],
        'lemonsqueezy' => [
            'currency' => 'USD',
            'api_key' => env('LEMONSQUEEZY_API_KEY'),
            'store_id' => env('LEMONSQUEEZY_STORE_ID'),
            'signing_secret' => env('LEMONSQUEEZY_SIGNING_SECRET'),
            'test_mode' => (bool) env('LEMONSQUEEZY_TEST_MODE', true),
        ],
    ],
];
