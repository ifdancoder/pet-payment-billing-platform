<?php

namespace App\Infrastructure\Subscription\Adapters\Persistence\Models;

use Illuminate\Database\Eloquent\Model;

class SubscriptionModel extends Model
{
    protected $table = 'subscriptions';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'merchant_id',
        'customer_id',
        'price_id',
        'product_id',
        'price_amount_minor_units',
        'price_currency',
        'billing_interval',
        'billing_interval_count',
        'status',
        'current_period_start',
        'current_period_end',
        'renewal_pending',
    ];

    protected function casts(): array
    {
        return [
            'current_period_start' => 'datetime',
            'current_period_end' => 'datetime',
            'renewal_pending' => 'boolean',
        ];
    }
}
