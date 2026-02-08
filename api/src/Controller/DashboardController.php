<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Repository\SupportTicketRepository;
use App\Repository\UserRepository;
use App\Service\DashboardTargets;
use DateTimeImmutable;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/dashboard')]
final class DashboardController extends AbstractController
{
    public function __construct(
        private readonly SupportTicketRepository $ticketRepository,
        private readonly UserRepository $userRepository,
    ) {
    }

    #[Route('/stats', name: 'api_dashboard_stats', methods: ['GET'])]
    public function stats(Request $request): JsonResponse
    {
        if (!$this->isGranted('OIDC_SUPPORT_EMPLOYEE') && !$this->isGranted('OIDC_SUPPORT_MANAGER') && !$this->isGranted('OIDC_ADMIN')) {
            throw new AccessDeniedHttpException();
        }

        $isManager = $this->isGranted('OIDC_SUPPORT_MANAGER') || $this->isGranted('OIDC_ADMIN');
        $userId = $request->query->get('userId');
        $period = $request->query->get('period', 'today');

        $user = null;
        if ($userId) {
            $user = $this->userRepository->find((int) $userId);
            if (!$user) {
                throw new BadRequestHttpException('User not found');
            }

            if (!$isManager) {
                $currentUser = $this->getUser();
                if (!$currentUser instanceof User || $currentUser->getId() !== $user->getId()) {
                    throw new AccessDeniedHttpException('You can only view your own dashboard');
                }
            }
        } elseif (!$isManager) {
            $user = $this->getUser();
            if (!$user instanceof User) {
                throw new AccessDeniedHttpException();
            }
        }

        [$from, $to] = $this->getPeriodRange($period);
        $daysInPeriod = max(1, (int) ceil(($to->getTimestamp() - $from->getTimestamp()) / 86400));

        $byStatus = $this->ticketRepository->getCountsByStatusForUser($user, $from, $to);
        $ticketsCompleted = $this->ticketRepository->getCompletedCountByUser($user, $from, $to);
        $ticketsTotal = $this->ticketRepository->getTotalCountByUser($user, $from, $to);
        $avgHandlingTime = $this->ticketRepository->getAverageHandlingTimeMinutes($user, $from, $to);
        $overdueCount = $this->ticketRepository->getOverdueCount($user, $from, $to, DashboardTargets::MAX_HANDLING_TIME_MINUTES);

        $overduePercent = $ticketsCompleted > 0
            ? round($overdueCount / $ticketsCompleted * 100, 1)
            : 0;

        return $this->json([
            'period' => $period,
            'periodStart' => $from->format('c'),
            'periodEnd' => $to->format('c'),
            'daysInPeriod' => $daysInPeriod,
            'ticketsCompleted' => $ticketsCompleted,
            'ticketsTotal' => $ticketsTotal,
            'ticketsCompletedPerDay' => round($ticketsCompleted / $daysInPeriod, 1),
            'averageHandlingTimeMinutes' => $avgHandlingTime,
            'overduePercent' => $overduePercent,
            'overdueCount' => $overdueCount,
            'byStatus' => $byStatus,
            'targets' => [
                'ticketsPerDay' => DashboardTargets::TICKETS_PER_DAY,
                'maxHandlingTimeMinutes' => DashboardTargets::MAX_HANDLING_TIME_MINUTES,
                'maxOverduePercent' => DashboardTargets::MAX_OVERDUE_PERCENT,
            ],
        ]);
    }

    #[Route('/employees', name: 'api_dashboard_employees', methods: ['GET'])]
    public function employees(Request $request): JsonResponse
    {
        if (!$this->isGranted('OIDC_SUPPORT_MANAGER') && !$this->isGranted('OIDC_ADMIN')) {
            throw new AccessDeniedHttpException();
        }

        $period = $request->query->get('period', 'today');
        [$from, $to] = $this->getPeriodRange($period);
        $daysInPeriod = max(1, (int) ceil(($to->getTimestamp() - $from->getTimestamp()) / 86400));

        $users = $this->userRepository->findAll();
        $result = [];

        foreach ($users as $user) {
            $completed = $this->ticketRepository->getCompletedCountByUser($user, $from, $to);
            $avgTime = $this->ticketRepository->getAverageHandlingTimeMinutes($user, $from, $to);
            $overdueCount = $this->ticketRepository->getOverdueCount($user, $from, $to, DashboardTargets::MAX_HANDLING_TIME_MINUTES);
            $overduePercent = $completed > 0 ? round($overdueCount / $completed * 100, 1) : 0;

            $result[] = [
                'id' => $user->getId(),
                'name' => $user->getName(),
                'ticketsCompleted' => $completed,
                'ticketsCompletedPerDay' => round($completed / $daysInPeriod, 1),
                'averageHandlingTimeMinutes' => $avgTime,
                'overduePercent' => $overduePercent,
            ];
        }

        return $this->json([
            'period' => $period,
            'employees' => $result,
            'targets' => [
                'ticketsPerDay' => DashboardTargets::TICKETS_PER_DAY,
                'maxHandlingTimeMinutes' => DashboardTargets::MAX_HANDLING_TIME_MINUTES,
                'maxOverduePercent' => DashboardTargets::MAX_OVERDUE_PERCENT,
            ],
        ]);
    }

    /**
     * @return array{\DateTimeImmutable, \DateTimeImmutable}
     */
    private function getPeriodRange(string $period): array
    {
        $now = new DateTimeImmutable();

        return match ($period) {
            'week' => [
                $now->modify('monday this week')->setTime(0, 0),
                $now->modify('+1 day')->setTime(0, 0),
            ],
            'month' => [
                $now->modify('first day of this month')->setTime(0, 0),
                $now->modify('+1 day')->setTime(0, 0),
            ],
            default => [
                $now->setTime(0, 0),
                $now->modify('+1 day')->setTime(0, 0),
            ],
        };
    }
}
