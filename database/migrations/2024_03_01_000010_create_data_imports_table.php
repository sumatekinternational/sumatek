<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Legacy migration / import jobs (§6.6): CSV upload, configurable column
 * mapping, dry-run preview, error report and rollback.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('data_imports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 24);            // sponsors | workers
            $table->string('source', 16)->default('csv');
            $table->string('original_filename')->nullable();
            $table->string('file_path');
            // pending | previewed | completed | failed | rolled_back
            $table->string('status', 16)->default('pending');
            $table->string('dedupe_strategy', 8)->default('skip'); // skip | update
            $table->json('mapping')->nullable();   // target_field => source_column
            $table->json('stats')->nullable();     // total/created/updated/skipped/errors
            $table->json('errors')->nullable();    // capped row-level error report
            $table->json('created_ids')->nullable(); // for rollback
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('data_imports');
    }
};
