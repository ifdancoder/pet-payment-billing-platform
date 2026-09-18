<?php

namespace App\Application\User\Ports\Outbound;

interface IPasswordHasherPort
{
    public function hash(string $plainPassword): string;

    public function verify(string $plainPassword, string $hash): bool;
}
