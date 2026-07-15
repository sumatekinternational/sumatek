<?php

use App\Http\Middleware\EnsureSubscriptionActive;
use App\Http\Middleware\ResolveTenant;
use App\Http\Middleware\SetLocale;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // The SPA and mobile apps authenticate with Sanctum bearer TOKENS, not
        // cookies — so we intentionally do NOT enable statefulApi() (that would
        // impose CSRF on token requests coming from a browser origin).

        // Localise API responses from the Accept-Language header (§7).
        $middleware->api(prepend: [SetLocale::class]);

        // Custom aliases used by route groups.
        $middleware->alias([
            'tenant' => ResolveTenant::class,
            'subscription' => EnsureSubscriptionActive::class,
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
        ]);

        // Resolve the tenant (and Spatie team) BEFORE route-model binding, so
        // bound models are fetched with the tenant scope in context rather than
        // being denied by the deny-by-default TenantScope.
        $middleware->prependToPriorityList(
            before: SubstituteBindings::class,
            prepend: ResolveTenant::class,
        );
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
