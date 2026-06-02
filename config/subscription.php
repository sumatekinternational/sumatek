<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Subscription lifecycle (§2)
    |--------------------------------------------------------------------------
    | active -> grace -> suspended -> archived
    */

    // Days after the paid period ends during which the tenant is still
    // writable but heavily nagged. After this it becomes read-only/locked.
    'grace_days' => (int) env('SUBSCRIPTION_GRACE_DAYS', 14),

    // Renewal reminders fire this many days before expiry.
    'reminder_days' => [60, 30, 7, 1],

    // What an expired (post-grace) tenant may do.
    // read_only: GET requests allowed, writes blocked. locked: fully blocked.
    'expired_policy' => env('SUBSCRIPTION_EXPIRED_POLICY', 'read_only'),

    /*
    |--------------------------------------------------------------------------
    | Plan tiers & feature flags (§2). Pricing is a business input (§13) and
    | is stored per-plan in the database; these are the default feature gates.
    |--------------------------------------------------------------------------
    */
    'tiers' => [
        'basic' => [
            'features' => [
                'sponsor_management',
                'worker_catalogue',
                'block_check',
                'identity_read.mrz_ocr',
            ],
            'limits' => ['users' => 5, 'workers' => 500],
        ],
        'pro' => [
            'features' => [
                'sponsor_management',
                'worker_catalogue',
                'block_check',
                'identity_read.mrz_ocr',
                'identity_read.paci',
                'contracts',
                'visa_pipeline',
                'payments',
            ],
            'limits' => ['users' => 25, 'workers' => 5000],
        ],
        'enterprise' => [
            'features' => ['*'],
            'limits' => ['users' => null, 'workers' => null],
        ],
    ],

];
