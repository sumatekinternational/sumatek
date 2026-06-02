<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Provenance link so an import can be rolled back cleanly (§6.6). */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['sponsors', 'workers'] as $name) {
            Schema::table($name, function (Blueprint $table) {
                $table->foreignId('data_import_id')->nullable()->after('tenant_id')
                    ->constrained('data_imports')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        foreach (['sponsors', 'workers'] as $name) {
            Schema::table($name, function (Blueprint $table) {
                $table->dropConstrainedForeignId('data_import_id');
            });
        }
    }
};
