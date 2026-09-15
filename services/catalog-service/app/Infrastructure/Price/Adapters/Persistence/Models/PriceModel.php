<?php

namespace App\Infrastructure\Price\Adapters\Persistence\Models;

use Illuminate\Database\Eloquent\Model;

class PriceModel extends Model
{
    protected $table = 'prices';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'product_id',
        'amount_minor_units',
        'currency',
        'billing_interval',
    ];
}
