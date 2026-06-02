<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Domestic-worker contracts, modelled on the PAM standard contract (§6.3).
 * Tracks the six-month warranty, replacement guarantee, e-signature and
 * notarisation, and refund of recruitment expense.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sponsor_id')->constrained()->restrictOnDelete();
            $table->foreignId('worker_id')->constrained()->restrictOnDelete();
            $table->string('contract_no');
            $table->string('type')->default('pam_standard');
            // draft | active | completed | terminated | transferred
            $table->string('status', 16)->default('draft');

            $table->unsignedBigInteger('monthly_salary')->default(0); // KWD fils
            $table->string('currency', 3)->default('KWD');
            $table->unsignedSmallInteger('duration_months')->default(24);
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();

            // Warranty (six-month rule) + replacement/insurance guarantees.
            $table->unsignedTinyInteger('warranty_months')->default(6);
            $table->date('warranty_ends_at')->nullable();
            $table->boolean('warranty_void')->default(false);
            $table->string('warranty_void_reason')->nullable();
            $table->unsignedTinyInteger('replacement_months')->default(3);
            $table->unsignedTinyInteger('insurance_years')->default(2);

            // Recruitment fee + refund tracking (warranty-window discontinue).
            $table->unsignedBigInteger('recruitment_fee')->default(0); // fils
            $table->unsignedBigInteger('refunded_amount')->default(0); // fils

            // E-signature + notarisation.
            $table->timestamp('signed_by_sponsor_at')->nullable();
            $table->timestamp('signed_by_worker_at')->nullable();
            $table->string('sponsor_signature_path')->nullable();
            $table->string('worker_signature_path')->nullable();
            $table->timestamp('notarized_at')->nullable();
            $table->string('notary_reference')->nullable();

            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'contract_no']);
            $table->index(['tenant_id', 'status']);
            $table->index('warranty_ends_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contracts');
    }
};
