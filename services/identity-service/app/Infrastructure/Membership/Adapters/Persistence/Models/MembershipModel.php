<?php

namespace App\Infrastructure\Membership\Adapters\Persistence\Models;

use Illuminate\Database\Eloquent\Model;

class MembershipModel extends Model
{
    protected $table = 'memberships';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'user_id',
        'merchant_id',
        'role',
    ];
}
