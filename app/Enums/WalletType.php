<?php

namespace App\Enums;

enum WalletType: string
{
    case Available = 'available';
    case Reserved = 'reserved';
    case Trading = 'trading';
    case Bonus = 'bonus';
    case System = 'system';

    public function label(): string
    {
        return match ($this) {
            self::Available => 'Available Balance',
            self::Reserved => 'Reserved Balance',
            self::Trading => 'Trading Balance',
            self::Bonus => 'Bonus Balance',
            self::System => 'System',
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
