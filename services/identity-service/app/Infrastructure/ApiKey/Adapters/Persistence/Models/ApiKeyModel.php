<?php

namespace App\Infrastructure\ApiKey\Adapters\Persistence\Models;

use Illuminate\Database\Eloquent\Model;

final class ApiKeyModel extends Model
{
    protected $table = 'api_keys';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $guarded = [];
    protected $casts = ['scopes' => 'array', 'revoked_at' => 'immutable_datetime', 'last_used_at' => 'immutable_datetime'];
}
