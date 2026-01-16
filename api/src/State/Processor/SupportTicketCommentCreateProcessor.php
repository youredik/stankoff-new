<?php

declare(strict_types=1);

namespace App\State\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\SupportTicketComment;
use App\Entity\User;
use App\Enum\SupportTicketStatus;
use App\Repository\SupportTicketRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

final readonly class SupportTicketCommentCreateProcessor implements ProcessorInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private Security $security,
        private SupportTicketRepository $supportTicketRepository,
    ) {
    }

    public function process(
        mixed $data,
        Operation $operation,
        array $uriVariables = [],
        array $context = [],
    ): SupportTicketComment {
        assert($data instanceof SupportTicketComment);

        $data->createdAt = new \DateTimeImmutable();

        // Assign current user if authenticated
        $user = $this->security->getUser();
        if ($user instanceof User) {
            $data->user = $user;
        }

        // Validate that user can only have one ticket in progress
        if ($data->status === SupportTicketStatus::IN_PROGRESS && $user instanceof User) {
            if ($this->supportTicketRepository->hasUserTicketInProgress($user, $data->supportTicket->getId())) {
                throw new BadRequestHttpException(
                    'У вас уже имеется заявка в работе. Отложите действующую заявку чтобы взять новую.',
                );
            }
        }

        $this->entityManager->persist($data);
        $this->entityManager->flush();

        return $data;
    }
}
