<?php

namespace App\Infrastructure\Notification\Adapters\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NotificationModel extends Model
{
    protected $table = 'notifications';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'merchant_id',
        'source_event_id',
        'type',
        'channel',
        'recipient',
        'subject',
        'body_text',
        'body_html',
        'status',
        'deduplication_key',
        'sent_at',
        'failed_at',
    ];

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
            'failed_at' => 'datetime',
        ];
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(NotificationDeliveryAttemptModel::class, 'notification_id');
    }
}
