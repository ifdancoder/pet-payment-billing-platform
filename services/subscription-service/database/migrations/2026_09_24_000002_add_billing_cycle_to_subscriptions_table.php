<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->timestampTz('current_period_start')->nullable();
            $table->timestampTz('current_period_end')->nullable()->index();
            $table->boolean('renewal_pending')->default(true)->index();
        });

        DB::table('subscriptions')->orderBy('id')->chunk(100, function ($subscriptions): void {
            foreach ($subscriptions as $subscription) {
                $start = new DateTimeImmutable($subscription->created_at);
                $unit = match ((int) $subscription->billing_interval) {
                    1 => 'day',
                    2 => 'week',
                    3 => 'month',
                    4 => 'year',
                };
                $end = $start->modify('+'.(int) $subscription->billing_interval_count." {$unit}");

                DB::table('subscriptions')->where('id', $subscription->id)->update([
                    'current_period_start' => $start,
                    'current_period_end' => $end,
                    'renewal_pending' => (int) $subscription->status === 1,
                ]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropColumn(['current_period_start', 'current_period_end', 'renewal_pending']);
        });
    }
};
