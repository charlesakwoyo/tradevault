<?php

namespace App\Enums;

enum OrderType: string
{
    case Market = 'market';
    case Limit = 'limit';
    case Stop = 'stop';

    public function label(): string
    {
        return match ($this) {
            self::Market => 'Market',
            self::Limit => 'Limit',
            self::Stop => 'Stop',
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
