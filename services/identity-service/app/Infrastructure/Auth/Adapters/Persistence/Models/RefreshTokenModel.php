<?php

namespace App\Infrastructure\Auth\Adapters\Persistence\Models;

use Illuminate\Database\Eloquent\Model;

final class RefreshTokenModel extends Model
{
    protected $table = 'auth_refresh_tokens';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'expires_at' => 'immutable_datetime',
            'revoked_at' => 'immutable_datetime',
        ];
    }
}
