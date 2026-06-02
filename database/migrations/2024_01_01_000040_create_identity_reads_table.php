<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Audit of every identity auto-read; never stores the raw image (§5/§9). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('identity_reads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('driver', 16);          // paci|hawyti|smartcard|mrz_ocr|fake
            $table->string('document_type', 16)->nullable(); // civil_id|passport
            $table->string('verification_level', 16)->nullable();
            $table->string('subject_hash', 64)->nullable();
            $table->boolean('succeeded')->default(false);
            $table->json('meta')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['tenant_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('identity_reads');
    }
};
