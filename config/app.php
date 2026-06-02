<?php

return [

    'name' => env('APP_NAME', 'Kuwait Manpower SaaS'),

    'env' => env('APP_ENV', 'production'),

    'debug' => (bool) env('APP_DEBUG', false),

    'url' => env('APP_URL', 'http://localhost'),

    // Kuwait timezone by default (§7).
    'timezone' => env('APP_TIMEZONE', 'Asia/Kuwait'),

    // Arabic is the default locale; English is the fallback (§7).
    'locale' => env('APP_LOCALE', 'ar'),

    'fallback_locale' => env('APP_FALLBACK_LOCALE', 'en'),

    'faker_locale' => env('APP_FAKER_LOCALE', 'ar_SA'),

    // Supported runtime-switchable locales.
    'supported_locales' => ['ar', 'en'],

    'cipher' => 'AES-256-CBC',

    'key' => env('APP_KEY'),

    'previous_keys' => [
        ...array_filter(
            explode(',', env('APP_PREVIOUS_KEYS', ''))
        ),
    ],

    'maintenance' => [
        'driver' => env('APP_MAINTENANCE_DRIVER', 'file'),
        'store' => env('APP_MAINTENANCE_STORE', 'database'),
    ],

];
