<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Both referenced tables belong to this service.
        Schema::create('memberships', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('merchant_id')->constrained('merchants')->cascadeOnDelete();
            $table->unsignedTinyInteger('role');

            $table->timestamps();

            $table->unique(['user_id', 'merchant_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('memberships');
    }
};
