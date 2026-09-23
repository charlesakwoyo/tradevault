<?php

namespace App\Enums;

enum WithdrawalStatus: string
{
    case Pending = 'pending';
    case UnderReview = 'under_review';
    case Approved = 'approved';
    case Processing = 'processing';
    case Completed = 'completed';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::UnderReview => 'Under Review',
            self::Approved => 'Approved',
            self::Processing => 'Processing',
            self::Completed => 'Completed',
            self::Rejected => 'Rejected',
            self::Cancelled => 'Cancelled',
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
