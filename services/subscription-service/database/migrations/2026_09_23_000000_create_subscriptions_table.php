<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('merchant_id');
            $table->uuid('customer_id');
            $table->uuid('price_id');
            $table->uuid('product_id');
            $table->unsignedBigInteger('price_amount_minor_units');
            $table->string('price_currency', 3);
            $table->unsignedTinyInteger('billing_interval');
            $table->unsignedInteger('billing_interval_count');
            $table->unsignedTinyInteger('status');
            $table->timestamps();

            $table->index(['merchant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
