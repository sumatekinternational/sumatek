<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Agency -> Sponsor recruitment-fee invoicing (§6.5). Invoices are only ever
 * raised against sponsors (employers), never workers — WPS / no-fee-to-worker
 * awareness is enforced at the application layer.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sponsor_id')->constrained()->restrictOnDelete();
            $table->foreignId('contract_id')->nullable()->constrained()->nullOnDelete();
            $table->string('invoice_no');
            // draft | issued | partially_paid | paid | overdue | void
            $table->string('status', 16)->default('draft');
            $table->string('currency', 3)->default('KWD');
            $table->unsignedBigInteger('subtotal')->default(0); // fils
            $table->unsignedBigInteger('tax')->default(0);       // fils
            $table->unsignedBigInteger('total')->default(0);     // fils
            $table->unsignedBigInteger('amount_paid')->default(0); // fils
            $table->timestamp('issued_at')->nullable();
            $table->date('due_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'invoice_no']);
            $table->index(['tenant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
