<?php

declare(strict_types=1);

namespace App\State\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\SupportTicketComment;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;

final readonly class SupportTicketCommentCreateProcessor implements ProcessorInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private Security $security,
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): SupportTicketComment
    {
        assert($data instanceof SupportTicketComment);

        $data->createdAt = new \DateTimeImmutable();

        // Assign current user if authenticated
        $user = $this->security->getUser();
        if ($user instanceof User) {
            $data->user = $user;
        }

        $this->entityManager->persist($data);
        $this->entityManager->flush();

        return $data;
    }
}
