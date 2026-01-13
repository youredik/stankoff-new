<?php

declare(strict_types=1);

namespace App\Controller;

use App\Enum\SupportTicketClosingReason;
use App\Enum\SupportTicketStatus;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/support-tickets')]
final class SupportTicketController extends AbstractController
{
    #[Route('/statuses', name: 'api_support_ticket_statuses', methods: ['GET'])]
    public function getStatuses(): JsonResponse
    {
        $statuses = array_map(
            static fn(SupportTicketStatus $status) => [
                'id' => $status->value,
                'name' => $status->getDisplayName(),
                'color' => $status->getColor(),
            ],
            SupportTicketStatus::cases(),
        );

        return $this->json($statuses);
    }

    #[Route('/closing-reasons', name: 'api_support_ticket_closing_reasons', methods: ['GET'])]
    public function getClosingReasons(): JsonResponse
    {
        $reasons = array_map(
            static fn(SupportTicketClosingReason $reason) => [
                'id' => $reason->value,
                'name' => $reason->getDisplayName(),
            ],
            SupportTicketClosingReason::cases(),
        );

        return $this->json($reasons);
    }
}
