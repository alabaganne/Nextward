<?php

// Paid credits for tailored CVs and cover letters. Off by default: everything is free until BILLING_ENABLED=true.
return [
    'enabled' => (bool) env('BILLING_ENABLED', false),
    'signup_credits' => (int) env('BILLING_SIGNUP_CREDITS', 1),
    // Each credit covers the first draft of a kit plus this many rewrites.
    'rewrites_per_kit' => (int) env('BILLING_REWRITES_PER_KIT', 3),

    // Prices are set by the owner before billing is turned on. Amounts in the smallest unit (millimes for TND, cents for USD).
    'packs' => [
        'starter' => ['credits' => 3, 'price_tnd' => env('BILLING_STARTER_TND'), 'price_usd' => env('BILLING_STARTER_USD'), 'lemonsqueezy_variant' => env('LEMONSQUEEZY_VARIANT_STARTER')],
        'standard' => ['credits' => 10, 'price_tnd' => env('BILLING_STANDARD_TND'), 'price_usd' => env('BILLING_STANDARD_USD'), 'lemonsqueezy_variant' => env('LEMONSQUEEZY_VARIANT_STANDARD')],
        'pro' => ['credits' => 25, 'price_tnd' => env('BILLING_PRO_TND'), 'price_usd' => env('BILLING_PRO_USD'), 'lemonsqueezy_variant' => env('LEMONSQUEEZY_VARIANT_PRO')],
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
