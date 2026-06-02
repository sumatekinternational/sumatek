<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sponsorship transfer / Tanazul workflow (§6.3): worker, current sponsor, new
 * sponsor, office and PAM department. Unauthorised transfer voids warranty.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contract_transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contract_id')->constrained()->cascadeOnDelete();
            $table->foreignId('worker_id')->constrained()->restrictOnDelete();
            $table->foreignId('from_sponsor_id')->constrained('sponsors')->restrictOnDelete();
            $table->foreignId('to_sponsor_id')->constrained('sponsors')->restrictOnDelete();

            // requested | office_review | pam_submitted | approved | rejected | completed
            $table->string('status', 16)->default('requested');
            $table->boolean('authorized')->default(true);
            $table->string('pam_reference')->nullable();
            $table->unsignedBigInteger('transfer_fee')->default(0); // fils
            $table->foreignId('requested_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('note')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contract_transfers');
    }
};
