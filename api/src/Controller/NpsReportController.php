<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\SupportTicketRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/reports/nps')]
final class NpsReportController extends AbstractController
{
    public function __construct(
        private readonly SupportTicketRepository $ticketRepository,
    ) {
    }

    #[Route('', name: 'api_reports_nps', methods: ['GET'])]
    public function report(Request $request): JsonResponse
    {
        $this->denyUnlessManager();

        [$from, $to] = $this->parseDateRange($request);
        $rows = $this->ticketRepository->findCompletedResolvedInPeriod($from, $to);

        return $this->json([
            'from' => $from->format('c'),
            'to' => $to->format('c'),
            'total' => count($rows),
            'rows' => $rows,
        ]);
    }

    #[Route('/export', name: 'api_reports_nps_export', methods: ['GET'])]
    public function export(Request $request): StreamedResponse
    {
        $this->denyUnlessManager();

        [$from, $to] = $this->parseDateRange($request);
        $rows = $this->ticketRepository->findCompletedResolvedInPeriod($from, $to);

        $response = new StreamedResponse(function () use ($rows): void {
            $handle = fopen('php://output', 'w');
            // UTF-8 BOM for Excel
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, [
                '# заявки',
                'Тема обращения',
                'Автор',
                'Контрагент',
                '# заказа',
                'Ответственный',
                'Дата создания',
                'Дата закрытия',
                'Время обработки (мин)',
                'Комментарий закрытия',
            ], ';');

            foreach ($rows as $row) {
                fputcsv($handle, [
                    $row['id'],
                    $row['subject'],
                    $row['author_name'],
                    $row['contractor'] ?? '',
                    $row['order_id'] ?? '',
                    $row['user_name'] ?? '',
                    (new \DateTimeImmutable($row['created_at']))->format('d.m.Y H:i'),
                    (new \DateTimeImmutable($row['closed_at']))->format('d.m.Y H:i'),
                    round((float) $row['handling_time_minutes'], 1),
                    $row['closing_comment'] ?? '',
                ], ';');
            }

            fclose($handle);
        });

        $filename = sprintf(
            'nps_report_%s_%s.csv',
            $from->format('Y-m-d'),
            $to->modify('-1 day')->format('Y-m-d'),
        );

        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');

        return $response;
    }

    private function denyUnlessManager(): void
    {
        if (!$this->isGranted('OIDC_SUPPORT_MANAGER') && !$this->isGranted('OIDC_ADMIN')) {
            throw new AccessDeniedHttpException();
        }
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
}
