<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Enum\SupportTicketClosingReason;
use App\Repository\SupportTicketRepository;
use App\Service\DashboardTargets;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/reports/analytics')]
final class AnalyticsReportController extends AbstractController
{
    public function __construct(
        private readonly SupportTicketRepository $ticketRepository,
    ) {
    }

    #[Route('/acceptance-time', name: 'api_analytics_acceptance_time', methods: ['GET'])]
    public function acceptanceTime(Request $request): JsonResponse
    {
        $this->denyUnlessSupport();
        [$from, $to] = $this->parseDateRange($request);
        $userId = $this->resolveUserId($request);

        $rows = $this->ticketRepository->findTicketsWithAcceptanceTime($from, $to, $userId);

        $withinSla = 0;
        $overdue = 0;
        foreach ($rows as $row) {
            if ((float) $row['acceptance_time_minutes'] <= DashboardTargets::MAX_ACCEPTANCE_TIME_MINUTES) {
                $withinSla++;
            } else {
                $overdue++;
            }
        }

        return $this->json([
            'from' => $from->format('c'),
            'to' => $to->format('c'),
            'total' => count($rows),
            'withinSla' => $withinSla,
            'overdue' => $overdue,
            'slaMinutes' => DashboardTargets::MAX_ACCEPTANCE_TIME_MINUTES,
            'rows' => $rows,
        ]);
    }

    #[Route('/acceptance-time/export', name: 'api_analytics_acceptance_time_export', methods: ['GET'])]
    public function acceptanceTimeExport(Request $request): StreamedResponse
    {
        $this->denyUnlessSupport();
        [$from, $to] = $this->parseDateRange($request);
        $userId = $this->resolveUserId($request);

        $rows = $this->ticketRepository->findTicketsWithAcceptanceTime($from, $to, $userId);

        return $this->streamCsv(
            sprintf('acceptance_time_%s_%s.csv', $from->format('Y-m-d'), $to->modify('-1 day')->format('Y-m-d')),
            ['# заявки', 'Название', 'Контрагент', 'Ответственный', 'Время принятия (мин)', 'В нормативе'],
            $rows,
            fn(array $row) => [
                $row['id'],
                $row['subject'],
                $row['contractor'] ?? '',
                $row['user_name'] ?? '',
                round((float) $row['acceptance_time_minutes'], 1),
                (float) $row['acceptance_time_minutes'] <= DashboardTargets::MAX_ACCEPTANCE_TIME_MINUTES ? 'Да' : 'Нет',
            ],
        );
    }

    #[Route('/resolution-time', name: 'api_analytics_resolution_time', methods: ['GET'])]
    public function resolutionTime(Request $request): JsonResponse
    {
        $this->denyUnlessSupport();
        [$from, $to] = $this->parseDateRange($request);
        $userId = $this->resolveUserId($request);

        $rows = $this->ticketRepository->findTicketsWithResolutionTime($from, $to, $userId);

        $withinSla = 0;
        $overdue = 0;
        foreach ($rows as $row) {
            if ((float) $row['resolution_time_minutes'] <= DashboardTargets::MAX_RESOLUTION_TIME_MINUTES) {
                $withinSla++;
            } else {
                $overdue++;
            }
        }

        return $this->json([
            'from' => $from->format('c'),
            'to' => $to->format('c'),
            'total' => count($rows),
            'withinSla' => $withinSla,
            'overdue' => $overdue,
            'slaMinutes' => DashboardTargets::MAX_RESOLUTION_TIME_MINUTES,
            'rows' => $rows,
        ]);
    }

    #[Route('/resolution-time/export', name: 'api_analytics_resolution_time_export', methods: ['GET'])]
    public function resolutionTimeExport(Request $request): StreamedResponse
    {
        $this->denyUnlessSupport();
        [$from, $to] = $this->parseDateRange($request);
        $userId = $this->resolveUserId($request);

        $rows = $this->ticketRepository->findTicketsWithResolutionTime($from, $to, $userId);

        return $this->streamCsv(
            sprintf('resolution_time_%s_%s.csv', $from->format('Y-m-d'), $to->modify('-1 day')->format('Y-m-d')),
            ['# заявки', 'Название', 'Контрагент', 'Ответственный', 'Время решения (мин)', 'В нормативе'],
            $rows,
            fn(array $row) => [
                $row['id'],
                $row['subject'],
                $row['contractor'] ?? '',
                $row['user_name'] ?? '',
                round((float) $row['resolution_time_minutes'], 1),
                (float) $row['resolution_time_minutes'] <= DashboardTargets::MAX_RESOLUTION_TIME_MINUTES ? 'Да' : 'Нет',
            ],
        );
    }

    #[Route('/closing-reasons', name: 'api_analytics_closing_reasons', methods: ['GET'])]
    public function closingReasons(Request $request): JsonResponse
    {
        $this->denyUnlessSupport();
        [$from, $to] = $this->parseDateRange($request);
        $userId = $this->resolveUserId($request);

        $rows = $this->ticketRepository->findCompletedWithClosingReasons($from, $to, $userId);
        $counts = $this->ticketRepository->getClosingReasonCounts($from, $to, $userId);

        $reasonLabels = [];
        foreach (SupportTicketClosingReason::cases() as $reason) {
            $reasonLabels[$reason->value] = $reason->getDisplayName();
        }

        $enrichedRows = array_map(static function (array $row) use ($reasonLabels) {
            $row['closing_reason_label'] = $reasonLabels[$row['closing_reason']] ?? $row['closing_reason'];
            return $row;
        }, $rows);

        return $this->json([
            'from' => $from->format('c'),
            'to' => $to->format('c'),
            'total' => count($rows),
            'counts' => $counts,
            'reasonLabels' => $reasonLabels,
            'rows' => $enrichedRows,
        ]);
    }

    #[Route('/closing-reasons/export', name: 'api_analytics_closing_reasons_export', methods: ['GET'])]
    public function closingReasonsExport(Request $request): StreamedResponse
    {
        $this->denyUnlessSupport();
        [$from, $to] = $this->parseDateRange($request);
        $userId = $this->resolveUserId($request);

        $rows = $this->ticketRepository->findCompletedWithClosingReasons($from, $to, $userId);

        $reasonLabels = [];
        foreach (SupportTicketClosingReason::cases() as $reason) {
            $reasonLabels[$reason->value] = $reason->getDisplayName();
        }

        return $this->streamCsv(
            sprintf('closing_reasons_%s_%s.csv', $from->format('Y-m-d'), $to->modify('-1 day')->format('Y-m-d')),
            ['# заявки', 'Название', 'Контрагент', 'Ответственный', 'Причина закрытия'],
            $rows,
            fn(array $row) => [
                $row['id'],
                $row['subject'],
                $row['contractor'] ?? '',
                $row['user_name'] ?? '',
                $reasonLabels[$row['closing_reason']] ?? $row['closing_reason'],
            ],
        );
    }

    #[Route('/hourly-distribution', name: 'api_analytics_hourly_distribution', methods: ['GET'])]
    public function hourlyDistribution(Request $request): JsonResponse
    {
        $this->denyUnlessSupport();
        [$from, $to] = $this->parseDateRange($request);

        $ticketsByHour = $this->ticketRepository->getHourlyDistribution($from, $to);
        $activityByHour = $this->ticketRepository->getHourlyActivity($from, $to);

        return $this->json([
            'from' => $from->format('c'),
            'to' => $to->format('c'),
            'ticketsByHour' => $ticketsByHour,
            'activityByHour' => $activityByHour,
        ]);
    }

    #[Route('/employee-summary', name: 'api_analytics_employee_summary', methods: ['GET'])]
    public function employeeSummary(Request $request): JsonResponse
    {
        if (!$this->isGranted('OIDC_SUPPORT_MANAGER') && !$this->isGranted('OIDC_ADMIN')) {
            throw new AccessDeniedHttpException();
        }

        [$from, $to] = $this->parseDateRange($request);
        $rows = $this->ticketRepository->getEmployeeSummaryForPeriod($from, $to);

        return $this->json([
            'from' => $from->format('c'),
            'to' => $to->format('c'),
            'sla' => [
                'acceptanceMinutes' => DashboardTargets::MAX_ACCEPTANCE_TIME_MINUTES,
                'resolutionMinutes' => DashboardTargets::MAX_RESOLUTION_TIME_MINUTES,
            ],
            'rows' => $rows,
        ]);
    }

    #[Route('/employee-summary/export', name: 'api_analytics_employee_summary_export', methods: ['GET'])]
    public function employeeSummaryExport(Request $request): StreamedResponse
    {
        if (!$this->isGranted('OIDC_SUPPORT_MANAGER') && !$this->isGranted('OIDC_ADMIN')) {
            throw new AccessDeniedHttpException();
        }

        [$from, $to] = $this->parseDateRange($request);
        $rows = $this->ticketRepository->getEmployeeSummaryForPeriod($from, $to);

        return $this->streamCsv(
            sprintf('employee_summary_%s_%s.csv', $from->format('Y-m-d'), $to->modify('-1 day')->format('Y-m-d')),
            ['Сотрудник', 'Завершено', 'Ср. время принятия (мин)', 'Ср. время решения (мин)', 'Просрочено принятие', 'Просрочено решение'],
            $rows,
            fn(array $row) => [
                $row['user_name'] ?? '',
                $row['completed_count'],
                $row['avg_acceptance_minutes'] !== null ? round((float) $row['avg_acceptance_minutes'], 1) : '',
                $row['avg_resolution_minutes'] !== null ? round((float) $row['avg_resolution_minutes'], 1) : '',
                $row['acceptance_overdue_count'],
                $row['resolution_overdue_count'],
            ],
        );
    }

    private function denyUnlessSupport(): void
    {
        if (
            !$this->isGranted('OIDC_SUPPORT_EMPLOYEE')
            && !$this->isGranted('OIDC_SUPPORT_MANAGER')
            && !$this->isGranted('OIDC_ADMIN')
        ) {
            throw new AccessDeniedHttpException();
        }
    }

    private function resolveUserId(Request $request): ?int
    {
        $isManager = $this->isGranted('OIDC_SUPPORT_MANAGER') || $this->isGranted('OIDC_ADMIN');

        if ($isManager) {
            $requestedUserId = $request->query->get('userId');
            return $requestedUserId !== null ? (int) $requestedUserId : null;
        }

        $user = $this->getUser();
        if ($user instanceof User) {
            return $user->getId();
        }

        throw new AccessDeniedHttpException();
    }

    /**
     * @return array{\DateTimeImmutable, \DateTimeImmutable}
     */
    private function parseDateRange(Request $request): array
    {
        $fromStr = $request->query->get('from');
        $toStr = $request->query->get('to');

        if (!$fromStr || !$toStr) {
            throw new BadRequestHttpException('Both "from" and "to" query parameters are required (format: YYYY-MM-DD)');
        }

        $from = \DateTimeImmutable::createFromFormat('Y-m-d', $fromStr);
        $to = \DateTimeImmutable::createFromFormat('Y-m-d', $toStr);

        if (!$from || !$to) {
            throw new BadRequestHttpException('Invalid date format. Use YYYY-MM-DD');
        }

        return [
            $from->setTime(0, 0),
            $to->modify('+1 day')->setTime(0, 0),
        ];
    }

    /**
     * @param string[] $headers
     * @param array[] $rows
     * @param callable(array): array $rowMapper
     */
    private function streamCsv(string $filename, array $headers, array $rows, callable $rowMapper): StreamedResponse
    {
        $response = new StreamedResponse(function () use ($headers, $rows, $rowMapper): void {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, $headers, ';');

            foreach ($rows as $row) {
                fputcsv($handle, $rowMapper($row), ';');
            }

            fclose($handle);
        });

        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');

        return $response;
    }
}
