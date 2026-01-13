<?php

declare(strict_types=1);

namespace App\Enum;

enum SupportTicketClosingReason: string
{
    case RESOLVED = 'resolved';
    case TRANSFERRED_TO_CLAIMS = 'transferred_to_claims';
    case TRANSFERRED_TO_SERVICE = 'transferred_to_service';
    case TRANSFERRED_TO_OP = 'transferred_to_op';

    public function getDisplayName(): string
    {
        return match ($this) {
            self::RESOLVED => 'Решено',
            self::TRANSFERRED_TO_CLAIMS => 'Передано в рекламации',
            self::TRANSFERRED_TO_SERVICE => 'Передано в сервис',
            self::TRANSFERRED_TO_OP => 'Передано в ОП',
        };
    }
}
