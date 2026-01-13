<?php

declare(strict_types=1);

namespace App\Enum;

enum SupportTicketStatus: string
{
    case NEW = 'new';
    case IN_PROGRESS = 'in_progress';
    case POSTPONED = 'postponed';
    case COMPLETED = 'completed';

    public function getDisplayName(): string
    {
        return match ($this) {
            self::NEW => 'Новая',
            self::IN_PROGRESS => 'В работе',
            self::POSTPONED => 'Отложено',
            self::COMPLETED => 'Завершено',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::NEW => 'info', // Blue for new
            self::IN_PROGRESS => 'warning', // Orange for in progress
            self::POSTPONED => 'secondary', // Gray for postponed
            self::COMPLETED => 'success', // Green for completed
        };
    }
}
