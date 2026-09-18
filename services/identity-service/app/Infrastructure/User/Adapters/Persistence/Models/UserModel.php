<?php

namespace App\Infrastructure\User\Adapters\Persistence\Models;

use Illuminate\Database\Eloquent\Model;

class UserModel extends Model
{
    protected $table = 'users';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'email',
        'password_hash',
        'status',
    ];

    protected $hidden = [
        'password_hash',
    ];
}
