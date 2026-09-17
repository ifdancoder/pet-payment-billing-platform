<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('merchant_id');
            $table->uuid('customer_id');
            $table->uuid('subscription_id');

            $table->timestampTz('period_start');
            $table->timestampTz('period_end');

            $table->string('currency', 3);
            $table->unsignedBigInteger('subtotal_amount_minor_units');
            $table->unsignedBigInteger('total_amount_minor_units');

            $table->unsignedTinyInteger('status');

            $table->uuid('payment_id')->nullable();
            $table->timestampTz('paid_at')->nullable();
            $table->timestampTz('voided_at')->nullable();

            $table->timestamps();

            $table->index(['merchant_id', 'status']);

            // Prevents duplicate invoices for one subscription period.
            $table->unique(['subscription_id', 'period_start']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
