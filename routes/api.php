<?php

use App\Http\Controllers\Admin\BlockModerationController;
use App\Http\Controllers\Admin\SubscriptionPlanController;
use App\Http\Controllers\Admin\TenantController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\EligibilityController;
use App\Http\Controllers\IdentityController;
use App\Http\Controllers\SponsorBlockController;
use App\Http\Controllers\SponsorController;
use App\Http\Controllers\WorkerController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    // ---------------------------------------------------------------------
    // Authentication (open)
    // ---------------------------------------------------------------------
    Route::post('auth/login', [AuthController::class, 'login'])
        ->middleware('throttle:auth')->name('auth.login');

    Route::middleware('auth:sanctum')->group(function () {

        Route::post('auth/logout', [AuthController::class, 'logout'])->name('auth.logout');
        Route::get('auth/me', [AuthController::class, 'me'])->name('auth.me');

        // -----------------------------------------------------------------
        // Vendor control plane (§2) — not tenant-scoped.
        // -----------------------------------------------------------------
        Route::prefix('admin')
            ->middleware('role:super-admin|vendor-support|vendor-billing')
            ->group(function () {
                Route::apiResource('tenants', TenantController::class);
                Route::post('tenants/{tenant}/suspend', [TenantController::class, 'suspend'])->name('tenants.suspend');
                Route::post('tenants/{tenant}/renew', [TenantController::class, 'renew'])->name('tenants.renew');

                Route::apiResource('plans', SubscriptionPlanController::class);

                // Block-registry moderation / governance (§4).
                Route::get('block-moderation', [BlockModerationController::class, 'index'])->name('moderation.index');
                Route::post('block-moderation/{block}/revoke', [BlockModerationController::class, 'revoke'])->name('moderation.revoke');
                Route::post('block-moderation/{block}/uphold', [BlockModerationController::class, 'uphold'])->name('moderation.uphold');
            });

        // -----------------------------------------------------------------
        // Tenant plane (§6) — tenant-scoped + subscription-gated.
        // -----------------------------------------------------------------
        Route::middleware(['tenant', 'subscription'])->group(function () {

            // FLAGSHIP: cross-agency sponsor eligibility check (§4).
            Route::post('eligibility/check', [EligibilityController::class, 'check'])
                ->middleware('permission:eligibility.check')
                ->name('eligibility.check');

            // Identity auto-read / auto-fill (§5).
            Route::post('identity/read', [IdentityController::class, 'read'])
                ->middleware('permission:identity.read')
                ->name('identity.read');
            Route::post('identity/hawyti/session', [IdentityController::class, 'hawytiSession'])
                ->middleware('permission:identity.read')
                ->name('identity.hawyti.session');

            // Sponsors (§6.1).
            Route::apiResource('sponsors', SponsorController::class);
            Route::post('sponsors/{sponsor}/block', [SponsorBlockController::class, 'block'])
                ->middleware('permission:sponsor.block')->name('sponsors.block');
            Route::post('sponsors/{sponsor}/unblock', [SponsorBlockController::class, 'unblock'])
                ->middleware('permission:sponsor.block')->name('sponsors.unblock');

            // Workers / CV bank (§6.2).
            Route::apiResource('workers', WorkerController::class);
        });
    });
});
