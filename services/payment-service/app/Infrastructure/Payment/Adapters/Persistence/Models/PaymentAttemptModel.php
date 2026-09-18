<?php

namespace App\Infrastructure\Payment\Adapters\Persistence\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentAttemptModel extends Model
{
    protected $table = 'payment_attempts';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'payment_id',
        'provider',
        'provider_reference',
        'status',
        'failure_code',
        'failure_message',
        'started_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }
}
