<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('invoice_lines', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('invoice_id')->constrained('invoices')->cascadeOnDelete();

            // References into catalog-service, kept nullable for ad-hoc lines
            // that aren't tied to a Product/Price. Never a foreign key: they
            // live in a different service's database.
            $table->uuid('product_id')->nullable();
            $table->uuid('price_id')->nullable();

            $table->string('description');
            $table->unsignedInteger('quantity');
            $table->unsignedBigInteger('unit_amount_minor_units');
            $table->unsignedBigInteger('total_amount_minor_units');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoice_lines');
    }
};
