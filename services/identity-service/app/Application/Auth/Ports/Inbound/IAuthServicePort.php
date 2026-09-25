<?php

namespace App\Application\Auth\Ports\Inbound;

use App\Application\Auth\DataTransferObjects\TokenPair;

interface IAuthServicePort
{
    public function register(string $email, string $password, string $merchantName): TokenPair;

    public function login(string $email, string $password, string $merchantId): TokenPair;

    public function refresh(string $refreshToken): TokenPair;

    public function logout(string $refreshToken): void;
}
