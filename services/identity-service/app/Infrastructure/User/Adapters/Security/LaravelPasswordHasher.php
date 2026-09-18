<?php

namespace App\Infrastructure\User\Adapters\Security;

use App\Application\User\Ports\Outbound\IPasswordHasherPort;
use Illuminate\Contracts\Hashing\Hasher;

final class LaravelPasswordHasher implements IPasswordHasherPort
{
    public function __construct(private readonly Hasher $hasher) {}

    public function hash(string $plainPassword): string
    {
        return $this->hasher->make($plainPassword);
    }

    public function verify(string $plainPassword, string $hash): bool
    {
        return $this->hasher->check($plainPassword, $hash);
    }
}
