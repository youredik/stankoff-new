<?php

declare(strict_types=1);

namespace App\Integration\Stankoff\Outbox;

enum OutboxStatus: string
{
    case PENDING = 'pending';
    case IN_PROGRESS = 'in_progress';
    case SUCCEEDED = 'succeeded';
    case PERMANENTLY_FAILED = 'permanently_failed';
}
