<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Payment gateways (§6.5)
    |--------------------------------------------------------------------------
    | Resolved through App\Services\Payments\PaymentGatewayInterface.
    | Drivers: knet | myfatoorah | sadad | manual
    |
    | WPS awareness: invoices are raised against sponsors (employers) only,
    | never workers — the platform must not facilitate charging fees to the
    | domestic worker.
    */

    'default' => env('PAYMENTS_DEFAULT_GATEWAY', 'manual'),

    'gateways' => [
        'knet' => [
            'tranportal_id' => env('KNET_TRANPORTAL_ID'),
            'tranportal_password' => env('KNET_TRANPORTAL_PASSWORD'),
            'resource_key' => env('KNET_RESOURCE_KEY'),
            'base_url' => env('KNET_BASE_URL'),
        ],
        'myfatoorah' => [
            'token' => env('MYFATOORAH_TOKEN'),
            'base_url' => env('MYFATOORAH_BASE_URL', 'https://apitest.myfatoorah.com'),
        ],
        'sadad' => [
            // SADAD references are typically reconciled out-of-band.
        ],
    ],

];
