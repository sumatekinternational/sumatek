<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->text('passport_no'); // encrypted
            $table->string('passport_no_hash', 64);
            $table->date('passport_expiry')->nullable();
            $table->string('name_ar')->nullable();
            $table->string('name_en');
            $table->string('nationality', 3);
            $table->date('date_of_birth')->nullable();
            $table->string('sex', 1)->nullable();
            $table->string('photo_path')->nullable();
            $table->json('skills')->nullable();
            $table->json('languages')->nullable();
            $table->unsignedTinyInteger('experience_years')->nullable();
            $table->string('medical_status', 64)->nullable();
            $table->string('status', 16)->default('available'); // available|reserved|deployed|returned|departed
            $table->string('source_agency')->nullable();
            $table->string('verification_level', 16)->default('unverified');
            $table->string('identity_source', 32)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'passport_no_hash']);
            $table->index(['tenant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workers');
    }
};
