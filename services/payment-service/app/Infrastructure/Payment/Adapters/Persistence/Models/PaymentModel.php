<?php

namespace App\Infrastructure\Payment\Adapters\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentModel extends Model
{
    protected $table = 'payments';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'invoice_id',
        'merchant_id',
        'customer_id',
        'amount_minor_units',
        'currency',
        'billing_reason',
        'status',
        'paid_at',
        'failed_at',
    ];

    protected function casts(): array
    {
        return [
            'paid_at' => 'datetime',
            'failed_at' => 'datetime',
        ];
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(PaymentAttemptModel::class, 'payment_id');
    }
}
