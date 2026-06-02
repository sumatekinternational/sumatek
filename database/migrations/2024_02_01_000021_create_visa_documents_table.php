<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Per-stage document checklist with auto-validation status (§6.4). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visa_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('visa_case_id')->constrained()->cascadeOnDelete();
            $table->string('stage', 24);
            $table->string('key');           // salary_certificate | rental_agreement | medical_report ...
            $table->string('label_en');
            $table->string('label_ar');
            $table->boolean('required')->default(true);
            $table->string('status', 16)->default('pending'); // pending | uploaded | verified | rejected
            $table->string('file_path')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();

            $table->index(['visa_case_id', 'stage']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visa_documents');
    }
};
