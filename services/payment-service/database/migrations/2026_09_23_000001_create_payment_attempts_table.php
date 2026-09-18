<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_attempts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('payment_id')->constrained('payments')->cascadeOnDelete();

            $table->string('provider');
            $table->string('provider_reference')->nullable();

            $table->unsignedTinyInteger('status');

            $table->string('failure_code')->nullable();
            $table->text('failure_message')->nullable();

            $table->timestampTz('started_at');
            $table->timestampTz('completed_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_attempts');
    }
};
