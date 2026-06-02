<?php

namespace App\Providers;

use App\Services\Payments\PaymentGatewayManager;
use Illuminate\Support\ServiceProvider;

class BillingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(PaymentGatewayManager::class, fn ($app) => new PaymentGatewayManager($app));
    }
}
