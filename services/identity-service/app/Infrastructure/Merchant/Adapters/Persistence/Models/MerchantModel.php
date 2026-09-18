<?php

namespace App\Infrastructure\Merchant\Adapters\Persistence\Models;

use Illuminate\Database\Eloquent\Model;

class MerchantModel extends Model
{
    protected $table = 'merchants';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'name',
        'status',
    ];
}
