<?php

namespace App\Providers;

use App\Services\Identity\IdentityReaderManager;
use Illuminate\Support\ServiceProvider;

class IdentityServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(IdentityReaderManager::class, function ($app) {
            return new IdentityReaderManager($app);
        });
    }
}
