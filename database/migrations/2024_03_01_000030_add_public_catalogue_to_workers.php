<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Consented public worker catalogue (§6.2). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('workers', function (Blueprint $table) {
            $table->boolean('public_listed')->default(false)->after('status');
            $table->uuid('public_token')->nullable()->unique()->after('public_listed');
            $table->timestamp('consent_public_at')->nullable()->after('public_token');
        });
    }

    public function down(): void
    {
        Schema::table('workers', function (Blueprint $table) {
            $table->dropColumn(['public_listed', 'public_token', 'consent_public_at']);
        });
    }
};
