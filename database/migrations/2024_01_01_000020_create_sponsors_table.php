<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sponsors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            // civil_id is stored encrypted (text); civil_id_hash is the keyed
            // lookup/dedupe value (§5/§9).
            $table->text('civil_id');
            $table->string('civil_id_hash', 64);
            $table->string('name_ar');
            $table->string('name_en')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('sex', 1)->nullable();
            $table->string('nationality', 3)->nullable();
            $table->text('address_ar')->nullable();
            $table->text('address_en')->nullable();
            $table->string('phone', 32)->nullable();
            $table->string('email')->nullable();
            $table->string('verification_level', 16)->default('unverified');
            $table->string('identity_source', 32)->nullable();
            $table->timestamp('consent_captured_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Civil ID is unique within an agency, not across the platform.
            $table->unique(['tenant_id', 'civil_id_hash']);
            $table->index('civil_id_hash');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sponsors');
    }
};
