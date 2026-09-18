<?php

namespace App\Domain\Membership\ValueObjects;

enum Role: int
{
    case Owner = 1;
    case Admin = 2;
    case Developer = 3;
    case Finance = 4;
    case Viewer = 5;

    public function label(): string
    {
        return match ($this) {
            self::Owner => 'owner',
            self::Admin => 'admin',
            self::Developer => 'developer',
            self::Finance => 'finance',
            self::Viewer => 'viewer',
        };
    }
}
