<?php

use App\Providers\AppServiceProvider;
use App\Providers\BillingServiceProvider;
use App\Providers\IdentityServiceProvider;

return [
    AppServiceProvider::class,
    IdentityServiceProvider::class,
    BillingServiceProvider::class,
];
