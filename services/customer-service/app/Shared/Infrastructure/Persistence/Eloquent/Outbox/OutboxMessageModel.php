<?php

namespace App\Shared\Infrastructure\Persistence\Eloquent\Outbox;

use Illuminate\Database\Eloquent\Model;

class OutboxMessageModel extends Model
{
    protected $table = 'outbox_messages';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'event_type',
        'aggregate_type',
        'aggregate_id',
        'payload',
        'occurred_at',
        'published_at',
        'attempts',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'occurred_at' => 'datetime',
            'published_at' => 'datetime',
        ];
    }
}
