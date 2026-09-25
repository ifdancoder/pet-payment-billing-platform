<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('api_keys', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('merchant_id')->constrained('merchants')->cascadeOnDelete();
            $table->string('name', 100);
            $table->char('secret_hash', 64)->unique();
            $table->string('role', 32);
            $table->json('scopes');
            $table->timestampTz('revoked_at')->nullable()->index();
            $table->timestampTz('last_used_at')->nullable();
            $table->timestampsTz();
            $table->index(['merchant_id', 'name']);
        });
    }
    public function down(): void { Schema::dropIfExists('api_keys'); }
};
