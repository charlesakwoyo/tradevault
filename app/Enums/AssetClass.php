<?php

namespace App\Enums;

enum AssetClass: string
{
    case Forex = 'forex';
    case Crypto = 'crypto';
    case Stock = 'stock';
    case Commodity = 'commodity';

    public function label(): string
    {
        return match ($this) {
            self::Forex => 'Forex',
            self::Crypto => 'Crypto',
            self::Stock => 'Stocks',
            self::Commodity => 'Commodities',
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
