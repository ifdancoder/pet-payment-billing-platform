<?php

namespace App\Shared\Infrastructure\Persistence\Eloquent\Inbox;

use Illuminate\Database\Eloquent\Model;

class InboxMessageModel extends Model
{
    protected $table = 'inbox_messages';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'event_type',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'processed_at' => 'datetime',
        ];
    }
}
