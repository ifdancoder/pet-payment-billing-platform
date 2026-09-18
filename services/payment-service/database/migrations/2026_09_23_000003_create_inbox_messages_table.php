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
        Schema::create('inbox_messages', function (Blueprint $table) {
            // The primary key IS the deduplication guarantee: id is the
            // inbound event's own event_id, so a second INSERT for the
            // same event_id fails on this unique primary key.
            $table->uuid('id')->primary();
            $table->string('event_type');
            $table->timestampTz('processed_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inbox_messages');
    }
};
