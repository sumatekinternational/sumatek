<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->string('name_en');
            $table->string('name_ar');
            $table->string('slug')->unique();
            $table->string('license_no')->nullable();
            $table->string('status')->default('onboarding'); // onboarding|active|suspended|archived
            $table->string('default_locale', 5)->default('ar');
            $table->string('contact_email')->nullable();
            $table->string('contact_phone', 32)->nullable();
            $table->json('allowed_identity_drivers')->nullable();
            $table->json('settings')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
