<?php

namespace App\Enums;

enum KycStatus: string
{
    case NotSubmitted = 'not_submitted';
    case Pending = 'pending';
    case UnderReview = 'under_review';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case RequiresMoreInformation = 'requires_more_information';

    public function label(): string
    {
        return match ($this) {
            self::NotSubmitted => 'Not Submitted',
            self::Pending => 'Pending',
            self::UnderReview => 'Under Review',
            self::Approved => 'Approved',
            self::Rejected => 'Rejected',
            self::RequiresMoreInformation => 'Requires More Information',
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
