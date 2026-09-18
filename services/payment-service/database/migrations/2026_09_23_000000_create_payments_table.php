<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('invoice_id');
            $table->uuid('merchant_id');
            $table->uuid('customer_id');

            $table->unsignedBigInteger('amount_minor_units');
            $table->string('currency', 3);

            $table->unsignedTinyInteger('status');

            $table->timestampTz('paid_at')->nullable();
            $table->timestampTz('failed_at')->nullable();

            $table->timestamps();

            $table->index(['merchant_id', 'status']);

            // Retries add attempts to the existing invoice payment.
            $table->unique('invoice_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
