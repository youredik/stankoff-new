<?php

declare(strict_types=1);

namespace App\State\Processor;

use ApiPlatform\Doctrine\Common\State\PersistProcessor;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\SupportTicketComment;
use App\Entity\User;
use Psr\Clock\ClockInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * @implements ProcessorInterface<SupportTicketComment, SupportTicketComment>
 */
final readonly class SupportTicketCommentCreateProcessor implements ProcessorInterface
{
    /**
     * @param ProcessorInterface<SupportTicketComment, SupportTicketComment> $persistProcessor
     */
    public function __construct(
        #[Autowire(service: PersistProcessor::class)]
        private ProcessorInterface $persistProcessor,
        private ClockInterface $clock,
        private TokenStorageInterface $tokenStorage,
    ) {
    }

    public function process(
        mixed $data,
        Operation $operation,
        array $uriVariables = [],
        array $context = [],
    ): SupportTicketComment {
        $data->createdAt = $this->clock->now();

        // Set the current authenticated user as the author
        $token = $this->tokenStorage->getToken();
        $user = $token?->getUser();
        if ($user instanceof User) {
            $data->user = $user;
        }

        return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
    }
}
