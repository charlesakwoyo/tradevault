<?php

namespace App\Enums;

enum FeeType: string
{
    case Deposit = 'deposit';
    case Withdrawal = 'withdrawal';
    case Trading = 'trading';
    case Network = 'network';
    case Conversion = 'conversion';

    public function label(): string
    {
        return match ($this) {
            self::Deposit => 'Deposit fee',
            self::Withdrawal => 'Withdrawal fee',
            self::Trading => 'Trading fee',
            self::Network => 'Network fee',
            self::Conversion => 'Conversion fee',
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
