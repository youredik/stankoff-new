<?php

declare(strict_types=1);

namespace App\State\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Service\YandexObjectStorageService;
use Doctrine\ORM\EntityManagerInterface;

class SupportTicketMediaDeleteProcessor implements ProcessorInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private YandexObjectStorageService $storageService,
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): void
    {
        // Delete from storage
        $this->storageService->deleteFile($data->path);

        // Delete from database
        $this->entityManager->remove($data);
        $this->entityManager->flush();
    }
}
