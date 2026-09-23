<?php

namespace App\Enums;

enum TicketStatus: string
{
    case Open = 'open';
    case InProgress = 'in_progress';
    case WaitingForUser = 'waiting_for_user';
    case Resolved = 'resolved';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Open',
            self::InProgress => 'In Progress',
            self::WaitingForUser => 'Waiting for User',
            self::Resolved => 'Resolved',
            self::Closed => 'Closed',
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
