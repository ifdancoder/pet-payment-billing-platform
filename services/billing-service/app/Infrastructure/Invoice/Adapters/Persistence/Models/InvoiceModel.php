<?php

namespace App\Infrastructure\Invoice\Adapters\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InvoiceModel extends Model
{
    protected $table = 'invoices';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'merchant_id',
        'customer_id',
        'subscription_id',
        'period_start',
        'period_end',
        'currency',
        'subtotal_amount_minor_units',
        'total_amount_minor_units',
        'status',
        'payment_id',
        'paid_at',
        'voided_at',
    ];

    protected function casts(): array
    {
        return [
            'period_start' => 'datetime',
            'period_end' => 'datetime',
            'paid_at' => 'datetime',
            'voided_at' => 'datetime',
        ];
    }

    public function lines(): HasMany
    {
        return $this->hasMany(InvoiceLineModel::class, 'invoice_id');
    }
}
