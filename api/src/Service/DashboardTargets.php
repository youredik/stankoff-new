<?php

declare(strict_types=1);

namespace App\Service;

final class DashboardTargets
{
    public const TICKETS_PER_DAY = 10;
    public const MAX_HANDLING_TIME_MINUTES = 120;
    public const MAX_OVERDUE_PERCENT = 10;
    public const MAX_ACCEPTANCE_TIME_MINUTES = 120;
    public const MAX_RESOLUTION_TIME_MINUTES = 2880;
}
