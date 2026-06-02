<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Domestic Worker Visa (Visa 20) deployment pipeline (§6.4). Kanban-style
 * stages with SLAs; SADAD fee payment tracking.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visa_cases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('worker_id')->constrained()->restrictOnDelete();
            $table->foreignId('sponsor_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('contract_id')->nullable()->constrained()->nullOnDelete();

            $table->string('visa_type')->default('visa_20');
            $table->string('stage', 24)->default('intake');
            $table->string('status', 16)->default('open'); // open | on_hold | completed | cancelled

            // SADAD fee tracking.
            $table->string('sadad_reference')->nullable();
            $table->unsignedBigInteger('sadad_amount')->default(0); // fils
            $table->timestamp('sadad_paid_at')->nullable();

            // SLA: when the current stage is due.
            $table->timestamp('entered_stage_at')->nullable();
            $table->timestamp('stage_due_at')->nullable();

            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'stage', 'status']);
            $table->index('stage_due_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visa_cases');
    }
};
