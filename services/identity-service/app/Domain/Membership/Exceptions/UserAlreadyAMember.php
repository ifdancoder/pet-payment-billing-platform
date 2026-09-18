<?php

namespace App\Domain\Membership\Exceptions;

use App\Domain\Merchant\ValueObjects\MerchantId;
use App\Domain\User\ValueObjects\UserId;
use RuntimeException;

final class UserAlreadyAMember extends RuntimeException
{
    public static function of(UserId $userId, MerchantId $merchantId): self
    {
        return new self("User \"{$userId->toString()}\" is already a member of merchant \"{$merchantId->toString()}\".");
    }
}
