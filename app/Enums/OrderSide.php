<?php

namespace App\Enums;

enum OrderSide: string
{
    case Buy = 'buy';
    case Sell = 'sell';

    public function label(): string
    {
        return match ($this) {
            self::Buy => 'Buy',
            self::Sell => 'Sell',
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
