<?php

declare(strict_types=1);

namespace App\State\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\SupportTicket;
use App\Enum\SupportTicketStatus;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;

final readonly class SupportTicketCreateProcessor implements ProcessorInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function process(
        mixed $data,
        Operation $operation,
        array $uriVariables = [],
        array $context = [],
    ): SupportTicket {
        assert($data instanceof SupportTicket);

        $data->createdAt = new DateTimeImmutable();
        $data->status = SupportTicketStatus::NEW;

        $this->entityManager->persist($data);
        $this->entityManager->flush();

        return $data;
    }
}
