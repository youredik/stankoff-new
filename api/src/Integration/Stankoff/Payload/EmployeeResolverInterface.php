<?php

declare(strict_types=1);

namespace App\Integration\Stankoff\Payload;

interface EmployeeResolverInterface
{
    /**
     * Returns the Stankoff employee_id for the given authorName, falling back
     * to a configured default if not found.
     */
    public function resolve(string $authorName): int;
}
