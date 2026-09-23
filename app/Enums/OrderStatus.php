<?php

namespace App\Enums;

enum OrderStatus: string
{
    case Pending = 'pending';
    case Open = 'open';
    case PartiallyFilled = 'partially_filled';
    case Filled = 'filled';
    case Cancelled = 'cancelled';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Open => 'Open',
            self::PartiallyFilled => 'Partially Filled',
            self::Filled => 'Filled',
            self::Cancelled => 'Cancelled',
            self::Rejected => 'Rejected',
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
