<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\SupportTicket;
use App\Entity\SupportTicketComment;
use App\Entity\User;
use App\Enum\SupportTicketClosingReason;
use App\Enum\SupportTicketStatus;
use App\Repository\SupportTicketRepository;
use App\Repository\UserRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/support-tickets')]
final class SupportTicketController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ValidatorInterface $validator,
        private readonly SupportTicketRepository $supportTicketRepository,
    ) {
    }

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

    #[Route('/assignable-users', name: 'api_support_ticket_assignable_users', methods: ['GET'])]
    public function getAssignableUsers(UserRepository $userRepository): JsonResponse
    {
        $users = $userRepository->findAll();
        $result = array_map(static fn(User $user) => [
            'id' => $user->getId(),
            'name' => $user->getName(),
        ], $users);

        return $this->json($result);
    }

    #[Route('/{id}/assign-user', name: 'api_support_ticket_assign_user', methods: ['POST'])]
    public function assignUser(Request $request, SupportTicket $supportTicket, UserRepository $userRepository): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!is_array($data)) {
            throw new BadRequestHttpException('Invalid JSON');
        }

        $userId = $data['userId'] ?? null;
        if (!$userId) {
            throw new BadRequestHttpException('User ID is required');
        }

        $user = $userRepository->find($userId);
        if (!$user) {
            throw new BadRequestHttpException('User not found');
        }

        if ($supportTicket->getCurrentStatusValue() === SupportTicketStatus::COMPLETED->value) {
            throw new BadRequestHttpException('Cannot assign user to completed ticket');
        }

        $supportTicket->user = $user;
        $this->entityManager->flush();

        return $this->json([
            'message' => 'User assigned successfully',
            'ticket' => [
                'id' => $supportTicket->getId(),
                'userName' => $supportTicket->getUserName(),
            ],
        ]);
    }

    #[IsGranted(new Expression('is_granted("OIDC_SUPPORT_EMPLOYEE") or is_granted("OIDC_SUPPORT_MANAGER")'))]
    #[Route('/{id}/change-status', name: 'api_support_ticket_change_status', methods: ['POST'])]
    public function changeStatus(Request $request, SupportTicket $supportTicket): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!is_array($data)) {
            throw new BadRequestHttpException('Invalid JSON');
        }

        $statusValue = $data['status'] ?? null;
        $commentText = $data['comment'] ?? '';
        $closingReasonValue = $data['closingReason'] ?? null;

        if (!$statusValue) {
            throw new BadRequestHttpException('Status is required');
        }

        $newStatus = SupportTicketStatus::tryFrom($statusValue);
        if (!$newStatus) {
            throw new BadRequestHttpException('Invalid status');
        }

        $currentStatus = $supportTicket->getCurrentStatusValue();

        // Validation rules
        if ($newStatus === SupportTicketStatus::NEW) {
            throw new BadRequestHttpException('Cannot change status to NEW');
        }

        if (($currentStatus === SupportTicketStatus::NEW->value) && $newStatus !== SupportTicketStatus::IN_PROGRESS) {
            throw new BadRequestHttpException('From NEW, can only change to IN_PROGRESS');
        }

        if ($currentStatus === SupportTicketStatus::COMPLETED->value) {
            throw new BadRequestHttpException('Cannot change status from COMPLETED');
        }

        $closingReason = null;
        if ($newStatus === SupportTicketStatus::COMPLETED) {
            if (!$closingReasonValue) {
                throw new BadRequestHttpException('Closing reason is required for COMPLETED status');
            }
            $closingReason = SupportTicketClosingReason::tryFrom($closingReasonValue);
            if (!$closingReason) {
                throw new BadRequestHttpException('Invalid closing reason');
            }

            $supportTicket->closedAt = new DateTimeImmutable();
        }

        // Update ticket user to current user if not already assigned
        $user = $this->getUser();
        $itsMe = $supportTicket->user === $user;
        if ($user instanceof User && $supportTicket->user === null) {
            $supportTicket->user = $user;
            $this->entityManager->flush();
        }

        // Validate that user can only have up to 3 tickets in progress
        if ($newStatus === SupportTicketStatus::IN_PROGRESS && $user instanceof User && $this->supportTicketRepository->hasUserTicketInProgress(
                $user,
                $supportTicket->getId(),
            )) {
            return $this->json(
                [
                    'error' => 'У '
                        . ($itsMe ? 'вас' : 'ответственного ' . $supportTicket->getUserName())
                        . ' уже имеется 3 заявки в работе.'
                        . ($itsMe ? ' Отложите действующую заявку чтобы взять новую.' : ''),
                ],
                400,
            );
        }

        // Create comment
        $comment = new SupportTicketComment();
        $comment->comment = $commentText;
        $comment->status = $newStatus;
        $comment->closingReason = $closingReason;
        $comment->supportTicket = $supportTicket;
        $comment->createdAt = new DateTimeImmutable();
        if ($user instanceof User) {
            $comment->user = $user;
        }

        $errors = $this->validator->validate($comment);
        if (count($errors) > 0) {
            throw new BadRequestHttpException((string)$errors);
        }

        $this->entityManager->persist($comment);
        $this->entityManager->flush();

        return $this->json([
            'message' => 'Status changed successfully',
            'ticket' => [
                'id' => $supportTicket->getId(),
                'currentStatus' => $supportTicket->getCurrentStatus(),
                'currentStatusValue' => $supportTicket->getCurrentStatusValue(),
                'userName' => $supportTicket->getUserName(),
            ],
        ]);
    }
}
