<?php

namespace App\Enums;

enum TransactionStatus: string
{
    case Pending = 'pending';
    case Completed = 'completed';
    case Failed = 'failed';
    case Reversed = 'reversed';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Completed => 'Completed',
            self::Failed => 'Failed',
            self::Reversed => 'Reversed',
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
