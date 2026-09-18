<?php

namespace App\Domain\Merchant\Exceptions;

use App\Domain\Merchant\ValueObjects\MerchantId;
use RuntimeException;

final class MerchantNotFound extends RuntimeException
{
    public static function withId(MerchantId $id): self
    {
        return new self("Merchant \"{$id->toString()}\" was not found.");
    }
}
