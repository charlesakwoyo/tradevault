<?php

namespace App\Enums;

enum RoleName: string
{
    case User = 'user';
    case Admin = 'admin';
    case Support = 'support';
    case Finance = 'finance';
    case Trading = 'trading';

    public function label(): string
    {
        return match ($this) {
            self::User => 'User',
            self::Admin => 'Administrator',
            self::Support => 'Support Staff',
            self::Finance => 'Finance Manager',
            self::Trading => 'Trading Manager',
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
