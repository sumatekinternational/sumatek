<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Central, CROSS-TENANT sponsor block registry (§4) — the network-effect moat.
 * Deliberately NOT tenant-scoped: blocking_tenant_id records which agency
 * asserted each block, while civil_id_hash enables cross-agency lookup.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sponsor_block_registry', function (Blueprint $table) {
            $table->id();
            $table->foreignId('blocking_tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->text('civil_id'); // encrypted
            $table->string('civil_id_hash', 64);
            $table->string('sponsor_name_ar')->nullable();
            $table->string('sponsor_name_en')->nullable();
            $table->string('reason_code', 32);
            $table->text('note')->nullable();
            $table->string('evidence_path')->nullable();
            $table->string('status', 16)->default('active'); // active | revoked | expired
            $table->timestamp('review_at')->nullable();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('revoked_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('revoked_reason')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->string('moderation_state', 16)->default('none'); // none|flagged|upheld|removed
            $table->foreignId('moderated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('consent_captured_at')->nullable();
            $table->timestamps();

            // One active assertion per (agency, civil id).
            $table->unique(['blocking_tenant_id', 'civil_id_hash']);
            // The hot path: cross-agency eligibility lookup by civil id + status.
            $table->index(['civil_id_hash', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sponsor_block_registry');
    }
};
