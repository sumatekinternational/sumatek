<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Append-only pipeline stage-transition log (§6.4). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visa_stage_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('visa_case_id')->constrained()->cascadeOnDelete();
            $table->string('from_stage', 24)->nullable();
            $table->string('to_stage', 24);
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('note')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index('visa_case_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visa_stage_events');
    }
};
