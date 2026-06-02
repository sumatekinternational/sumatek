<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Block dispute workflow (§4): a sponsor (via an agency) or an agency can
 * dispute a registry block; the vendor mediates. Cross-tenant — the disputing
 * agency may differ from the blocking agency.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('block_disputes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('block_id')->constrained('sponsor_block_registry')->cascadeOnDelete();
            $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete(); // disputing agency
            $table->foreignId('raised_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('disputant_type', 12)->default('agency'); // agency | sponsor
            $table->text('reason');
            // open | under_review | resolved_upheld | resolved_removed | rejected
            $table->string('status', 24)->default('open');
            $table->text('resolution_note')->nullable();
            $table->foreignId('resolved_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('block_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('block_disputes');
    }
};
