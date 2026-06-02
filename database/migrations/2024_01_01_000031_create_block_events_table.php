<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Append-only lifecycle log for each block (§4). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('block_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('block_id')->constrained('sponsor_block_registry')->cascadeOnDelete();
            $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type', 24); // blocked|reviewed|unblocked|disputed|upheld|removed
            $table->string('reason_code', 32)->nullable();
            $table->text('note')->nullable();
            $table->json('snapshot')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index('block_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('block_events');
    }
};
