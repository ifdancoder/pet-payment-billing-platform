<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const LEGACY_MERCHANT_ID = '00000000-0000-4000-8000-000000000000';

    public function up(): void
    {
        if (Schema::hasColumn('customers', 'merchant_id')) {
            return;
        }

        Schema::table('customers', function (Blueprint $table): void {
            $table->uuid('merchant_id')->nullable()->after('id');
        });

        // Customer rows created before authentication had no tenant that could
        // be recovered. Keep them quarantined under a non-user sentinel rather
        // than exposing them to the first real merchant after the upgrade.
        DB::table('customers')->whereNull('merchant_id')->update([
            'merchant_id' => self::LEGACY_MERCHANT_ID,
        ]);

        DB::statement('ALTER TABLE customers ALTER COLUMN merchant_id SET NOT NULL');

        Schema::table('customers', function (Blueprint $table): void {
            $table->dropUnique('customers_email_unique');
            $table->unique(['merchant_id', 'email']);
            $table->index('merchant_id');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('customers', 'merchant_id')) {
            return;
        }

        Schema::table('customers', function (Blueprint $table): void {
            $table->dropUnique(['merchant_id', 'email']);
            $table->dropIndex(['merchant_id']);
            $table->dropColumn('merchant_id');
            $table->unique('email');
        });
    }
};
