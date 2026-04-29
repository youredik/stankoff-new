<?php

declare(strict_types=1);

namespace App\Integration\Stankoff\Payload;

use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Resolves a free-text authorName (e.g. "Дмитрий Мыслюк") into a Stankoff
 * employee id (int) by looking up a static read-only CSV map of all known
 * employees. The CSV is loaded once at construction.
 *
 * If the name doesn't match any employee, the configured fallback id is used
 * (and the miss is logged at WARNING level for ops visibility).
 *
 * Why CSV-not-DB: this is a temporary bridge until Stankoff exposes a stable
 * employees API. Refreshing means redeploying with a new CSV — no migration.
 */
final class EmployeeResolver implements EmployeeResolverInterface
{
    /** @var array<string, int>  normalized "firstName lastName" => employeeId */
    private array $byFullName = [];

    public function __construct(
        #[Autowire(param: 'kernel.project_dir')] string $projectDir,
        #[Autowire(env: 'int:STANKOFF_FALLBACK_EMPLOYEE_ID')] private readonly int $fallbackEmployeeId,
        private readonly LoggerInterface $logger,
        ?string $csvPath = null,
    ) {
        $path = $csvPath ?? $projectDir . '/config/integrations/stankoff_employees.csv';
        $this->loadCsv($path);
    }

    public function resolve(string $authorName): int
    {
        $normalized = $this->normalize($authorName);
        if ($normalized === '') {
            return $this->fallback($authorName, 'empty');
        }

        if (isset($this->byFullName[$normalized])) {
            return $this->byFullName[$normalized];
        }

        // try reversed (lastName firstName)
        $parts = preg_split('/\s+/', $normalized) ?: [];
        if (count($parts) === 2) {
            $reversed = $parts[1] . ' ' . $parts[0];
            if (isset($this->byFullName[$reversed])) {
                return $this->byFullName[$reversed];
            }
        }

        return $this->fallback($authorName, 'no_match');
    }

    private function fallback(string $authorName, string $reason): int
    {
        $this->logger->warning('stankoff: authorName not mapped, using fallback', [
            'authorName' => $authorName,
            'reason' => $reason,
            'fallbackEmployeeId' => $this->fallbackEmployeeId,
        ]);

        return $this->fallbackEmployeeId;
    }

    private function loadCsv(string $path): void
    {
        if (!is_readable($path)) {
            throw new \RuntimeException("Employees CSV not readable: {$path}");
        }

        $handle = fopen($path, 'rb');
        if ($handle === false) {
            throw new \RuntimeException("Cannot open employees CSV: {$path}");
        }

        try {
            // Pass empty escape to silence PHP 8.4 deprecation; we don't use backslash-escapes.
            $header = fgetcsv($handle, 0, ',', '"', '');
            if ($header !== ['employeeId', 'firstName', 'lastName']) {
                throw new \RuntimeException(
                    'Unexpected CSV header in ' . $path . ': ' . json_encode($header, JSON_UNESCAPED_UNICODE),
                );
            }

            while (($row = fgetcsv($handle, 0, ',', '"', '')) !== false) {
                if (count($row) < 3) {
                    continue;
                }
                [$idRaw, $first, $last] = $row;
                $eid = (int)$idRaw;
                if ($eid <= 0) {
                    continue;
                }

                $first = trim((string)$first);
                $last = trim((string)$last);

                if ($first !== '' && $last !== '') {
                    $key = $this->normalize($first . ' ' . $last);
                    // ambiguity: per cross-check on prod data (29 Apr 2026) we have 0 collisions;
                    // keep the first occurrence if any sneak in later — resolve() will still find a match
                    $this->byFullName[$key] ??= $eid;
                }
            }
        } finally {
            fclose($handle);
        }
    }

    private function normalize(string $s): string
    {
        $s = trim($s);
        $s = preg_replace('/\s+/u', ' ', $s) ?? $s;
        $s = mb_strtolower($s, 'UTF-8');
        // ё/е collapse — observed inconsistency between authorName and CSV ("Карасёв" vs "Карасев")
        return strtr($s, ['ё' => 'е']);
    }
}
