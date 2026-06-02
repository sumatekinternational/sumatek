<?php

use App\Http\Controllers\Admin\BlockModerationController;
use App\Http\Controllers\Admin\SubscriptionPlanController;
use App\Http\Controllers\Admin\TenantController;
use App\Http\Controllers\Admin\VendorDashboardController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\ContractController;
use App\Http\Controllers\ContractTransferController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EligibilityController;
use App\Http\Controllers\IdentityController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\SponsorBlockController;
use App\Http\Controllers\SponsorController;
use App\Http\Controllers\VisaCaseController;
use App\Http\Controllers\VisaDocumentController;
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

                // Vendor dashboard (§6.8).
                Route::get('dashboard', VendorDashboardController::class)->name('admin.dashboard');
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

            // Contracts & compliance (§6.3).
            Route::apiResource('contracts', ContractController::class)->except('destroy');
            Route::post('contracts/{contract}/sign', [ContractController::class, 'sign'])->name('contracts.sign');
            Route::post('contracts/{contract}/activate', [ContractController::class, 'activate'])->name('contracts.activate');
            Route::post('contracts/{contract}/refund', [ContractController::class, 'refund'])->name('contracts.refund');
            Route::post('contracts/{contract}/transfers', [ContractTransferController::class, 'store'])->name('contracts.transfers.store');
            Route::post('transfers/{transfer}/advance', [ContractTransferController::class, 'advance'])->name('transfers.advance');

            // Visa & deployment pipeline (§6.4).
            Route::get('visa-cases/board', [VisaCaseController::class, 'board'])->name('visa.board');
            Route::apiResource('visa-cases', VisaCaseController::class)
                ->except('update', 'destroy')
                ->parameters(['visa-cases' => 'visaCase']);
            Route::post('visa-cases/{visaCase}/advance', [VisaCaseController::class, 'advance'])->name('visa.advance');
            Route::post('visa-cases/{visaCase}/sadad', [VisaCaseController::class, 'sadad'])->name('visa.sadad');
            Route::post('visa-documents/{document}/upload', [VisaDocumentController::class, 'upload'])->name('visa.docs.upload');
            Route::post('visa-documents/{document}/review', [VisaDocumentController::class, 'review'])->name('visa.docs.review');

            // Billing, invoicing & payments (§6.5).
            Route::apiResource('invoices', InvoiceController::class)->only(['index', 'store', 'show']);
            Route::post('invoices/{invoice}/issue', [InvoiceController::class, 'issue'])->name('invoices.issue');
            Route::post('invoices/{invoice}/void', [InvoiceController::class, 'void'])->name('invoices.void');
            Route::post('invoices/{invoice}/payments', [PaymentController::class, 'store'])->name('invoices.payments.store');

            // Agency dashboard (§6.8).
            Route::get('dashboard', DashboardController::class)->name('dashboard');
        });
    });
});
