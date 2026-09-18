<?php

namespace App\Infrastructure\Notification\Adapters\Persistence\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationDeliveryAttemptModel extends Model
{
    protected $table = 'notification_delivery_attempts';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'notification_id',
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
