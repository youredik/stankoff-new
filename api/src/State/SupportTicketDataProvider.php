<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Doctrine\Orm\Paginator;
use ApiPlatform\Doctrine\Orm\State\CollectionProvider;
use ApiPlatform\Doctrine\Orm\State\ItemProvider;
use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Entity\SupportTicket;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @implements ProviderInterface<SupportTicket>
 */
final readonly class SupportTicketDataProvider implements ProviderInterface
{
    public function __construct(
        private CollectionProvider $collectionProvider,
        private ItemProvider $itemProvider,
        private RequestStack $requestStack,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        if ($operation instanceof CollectionOperationInterface) {
            $request = $this->requestStack->getCurrentRequest();
            if ($request instanceof Request) {
                $queryParams = $request->query->all();
                error_log('SupportTicketDataProvider: query params: ' . json_encode($queryParams));
            }

            /** @var Collection $tickets */
            $tickets = $this->collectionProvider->provide($operation, $uriVariables, $context);

            if (!$this->isIterableEmpty($tickets) && $this->shouldSortByStatus($operation)) {
                $this->sortByStatus($tickets);
            }

            return $tickets;
        }

        return $this->itemProvider->provide($operation, $uriVariables, $context);
    }

    private function shouldSortByStatus(Operation $operation): bool
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request instanceof Request) {
            return false;
        }

        $all = $request->query->all();
        if (!isset($all['order']) || !is_array($all['order'])) {
            return false;
        }

        $order = $all['order'];
        if (!isset($order['currentStatusValue'])) {
            return false;
        }

        $direction = strtoupper($order['currentStatusValue']);
        return in_array($direction, ['ASC', 'DESC']);
    }

    private function sortByStatus(&$tickets): void
    {
        $request = $this->requestStack->getCurrentRequest();
        $all = $request->query->all();
        $order = $all['order'];
        $direction = strtoupper($order['currentStatusValue']);

        if ($tickets instanceof Paginator) {
            $tickets = iterator_to_array($tickets->getIterator());
        }

        usort($tickets, static function (SupportTicket $a, SupportTicket $b) use ($direction): int {
            $statusOrder = [
                'new' => 1,
                'in_progress' => 2,
                'postponed' => 3,
                'completed' => 4,
            ];
            $aStatus = $statusOrder[$a->getCurrentStatusValue()] ?? 999;
            $bStatus = $statusOrder[$b->getCurrentStatusValue()] ?? 999;

            if ($direction === 'ASC') {
                return $aStatus <=> $bStatus;
            }

            return $bStatus <=> $aStatus;
        });
    }

    private function isIterableEmpty(iterable $data): bool
    {
        if (is_countable($data) || $data instanceof \Countable) {
            return count($data) === 0;
        }

        return true;
    }
}
