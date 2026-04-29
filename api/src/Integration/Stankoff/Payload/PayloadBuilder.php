<?php

declare(strict_types=1);

namespace App\Integration\Stankoff\Payload;

use App\Entity\SupportTicket;

/**
 * Maps a SupportTicket entity into the JSON body Stankoff expects.
 *
 * Required fields are always present; optional fields are only included when
 * non-empty (cleaner payload, less PII spilled into logs/network).
 */
final class PayloadBuilder
{
    public function __construct(
        private readonly EmployeeResolverInterface $employeeResolver,
    ) {
    }

    /**
     * @param list<string> $attachmentIds Stankoff Files API ids ("fil_…")
     * @return array<string, mixed>
     */
    public function build(SupportTicket $ticket, array $attachmentIds = []): array
    {
        $orderData = is_array($ticket->orderData) ? $ticket->orderData : [];

        // Required fields — always sent.
        $inner = [
            'id' => $ticket->getId(),
            'title' => $ticket->subject,
            'description' => $ticket->description,
            'order_id' => $ticket->orderId,
            'author_employee_id' => $this->employeeResolver->resolve($ticket->authorName),
        ];

        // Optional fields — included only if non-empty.
        if ($itemIds = $this->extractOrderItemIds($orderData)) {
            $inner['order_item_ids'] = $itemIds;
        }
        if ($name = self::nullIfBlank($orderData['contactName'] ?? null)) {
            $inner['client_contact_name'] = $name;
        }
        if ($phone = self::nullIfBlank($orderData['contactPhone'] ?? null)) {
            $inner['client_contact_phone'] = $phone;
        }
        if ($email = self::nullIfBlank($orderData['contactEmail'] ?? null)) {
            $inner['client_contact_email'] = $email;
        }
        if ($attachmentIds) {
            $inner['attachment_ids'] = array_values($attachmentIds);
        }

        return [
            'eventType' => 'stankoff.support_ticket.created.v1',
            'occurredAt' => self::iso8601Ms($ticket->createdAt),
            'payload' => $inner,
        ];
    }

    /**
     * Parses orderData.selectedItems entries like ["50485_product", "59611_product"]
     * into [50485, 59611]. Non-numeric entries are silently skipped.
     *
     * @return list<int>
     */
    private function extractOrderItemIds(array $orderData): array
    {
        $items = $orderData['selectedItems'] ?? [];
        if (!is_array($items)) {
            return [];
        }

        $ids = [];
        foreach ($items as $raw) {
            if (preg_match('/^(\d+)/', (string)$raw, $m)) {
                $ids[] = (int)$m[1];
            }
        }
        return $ids;
    }

    private static function iso8601Ms(\DateTimeInterface $dt): string
    {
        $utc = \DateTimeImmutable::createFromInterface($dt)->setTimezone(new \DateTimeZone('UTC'));
        return $utc->format('Y-m-d\TH:i:s.v\Z');
    }

    private static function nullIfBlank(mixed $v): ?string
    {
        if (!is_string($v)) {
            return null;
        }
        $v = trim($v);
        return $v === '' ? null : $v;
    }
}
