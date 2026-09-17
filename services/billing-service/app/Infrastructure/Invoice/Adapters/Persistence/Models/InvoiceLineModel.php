<?php

namespace App\Infrastructure\Invoice\Adapters\Persistence\Models;

use Illuminate\Database\Eloquent\Model;

class InvoiceLineModel extends Model
{
    protected $table = 'invoice_lines';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'invoice_id',
        'product_id',
        'price_id',
        'description',
        'quantity',
        'unit_amount_minor_units',
        'total_amount_minor_units',
    ];
}
