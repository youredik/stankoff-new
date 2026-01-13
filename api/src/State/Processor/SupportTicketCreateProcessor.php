<?php

declare(strict_types=1);

namespace App\State\Processor;

use ApiPlatform\Doctrine\Common\State\PersistProcessor;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\SupportTicket;
use App\Entity\User;
//use App\Service\CamundaService;
use Psr\Clock\ClockInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * @implements ProcessorInterface<SupportTicket, SupportTicket>
 */
final readonly class SupportTicketCreateProcessor implements ProcessorInterface
{
    public function __construct(
        #[Autowire(service: PersistProcessor::class)]
        private ProcessorInterface $persistProcessor,
//        private CamundaService $camundaRepository,
        private ClockInterface $clock,
        private TokenStorageInterface $tokenStorage,
    ) {
    }

    public function process(
        mixed $data,
        Operation $operation,
        array $uriVariables = [],
        array $context = [],
    ): SupportTicket {
        $data->createdAt = $this->clock->now();

        // Convert orderId from string to int if present
        if (isset($data->orderId) && is_string($data->orderId)) {
            $data->orderId = $data->orderId === '' ? null : (int) $data->orderId;
        }

        // Set the current authenticated user as the author
        $token = $this->tokenStorage->getToken();
        $user = $token?->getUser();
        if ($user instanceof User) {
            $data->user = $user;
        }

        /** @var SupportTicket $supportTicket */
        $supportTicket = $this->persistProcessor->process($data, $operation, $uriVariables, $context);

        try {
//            $processResult = $this->camundaRepository->startProcess([
//                'processDefinitionId' => 'technicalSupport_v1.9',
//                'variables' => [
//                    'telegramChatId' => 30843047,
//                    'supportTicketId' => $supportTicket->getId(),
//                ],
//            ]);
//
//            // Save the process instance key in the ticket
//            $supportTicket->processInstanceKey = $processResult['processInstanceKey'] ?? null;

            // Persist the updated ticket with process instance key
            $this->persistProcessor->process($supportTicket, $operation, $uriVariables, $context);
        } catch (\Exception $e) {
            // Log error but don't fail the ticket creation
            error_log('Camunda process start failed: ' . $e->getMessage());
        }

        return $supportTicket;
    }
}
