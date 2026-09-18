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
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('merchant_id');
            $table->string('source_event_id');

            $table->unsignedTinyInteger('type');
            $table->unsignedTinyInteger('channel');
            $table->string('recipient');

            $table->string('subject');
            $table->text('body_text');
            $table->text('body_html');

            $table->unsignedTinyInteger('status');

            // Two different upstream event ids can represent the same
            // underlying business fact (e.g. a redelivered event under a
            // new id), so this — not source_event_id — is what prevents a
            // duplicate notification from being created.
            $table->string('deduplication_key')->unique();

            $table->timestampTz('sent_at')->nullable();
            $table->timestampTz('failed_at')->nullable();

            $table->timestamps();

            $table->index(['merchant_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
