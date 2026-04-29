<?php

declare(strict_types=1);

namespace App\Tests\Unit\Integration\Stankoff;

use App\Entity\SupportTicket;
use App\Enum\SupportTicketStatus;
use App\Integration\Stankoff\Payload\EmployeeResolverInterface;
use App\Integration\Stankoff\Payload\PayloadBuilder;
use PHPUnit\Framework\TestCase;

final class PayloadBuilderTest extends TestCase
{
    private function builder(int $employeeId = 148): PayloadBuilder
    {
        $resolver = $this->createMock(EmployeeResolverInterface::class);
        $resolver->method('resolve')->willReturn($employeeId);
        return new PayloadBuilder($resolver);
    }

    private function ticket(?callable $configure = null): SupportTicket
    {
        $t = new SupportTicket();
        $t->subject = 'Subj';
        $t->description = 'Desc';
        $t->authorName = 'Author';
        $t->status = SupportTicketStatus::NEW;
        $t->createdAt = new \DateTimeImmutable('2026-04-29 08:00:00.123', new \DateTimeZone('UTC'));

        // emulate Doctrine-assigned id via reflection (id is private)
        $r = new \ReflectionProperty(SupportTicket::class, 'id');
        $r->setValue($t, 4242);

        if ($configure) {
            $configure($t);
        }
        return $t;
    }

    public function testRequiredFieldsArePresent(): void
    {
        $payload = $this->builder(87)->build($this->ticket(fn(SupportTicket $t) => $t->orderId = 1001));
        self::assertSame('stankoff.support_ticket.created.v1', $payload['eventType']);
        self::assertSame('2026-04-29T08:00:00.123Z', $payload['occurredAt']);
        self::assertSame(4242, $payload['payload']['id']);
        self::assertSame('Subj', $payload['payload']['title']);
        self::assertSame('Desc', $payload['payload']['description']);
        self::assertSame(1001, $payload['payload']['order_id']);
        self::assertSame(87, $payload['payload']['author_employee_id']);
    }

    public function testNullOrderIdIsSentAsNull(): void
    {
        $payload = $this->builder()->build($this->ticket(fn(SupportTicket $t) => $t->orderId = null));
        self::assertArrayHasKey('order_id', $payload['payload']);
        self::assertNull($payload['payload']['order_id']);
    }

    public function testOptionalContactFieldsOmittedWhenEmpty(): void
    {
        $payload = $this->builder()->build($this->ticket(function(SupportTicket $t): void {
            $t->orderData = ['contactName' => '', 'contactPhone' => '', 'contactEmail' => ''];
        }));
        self::assertArrayNotHasKey('client_contact_name', $payload['payload']);
        self::assertArrayNotHasKey('client_contact_phone', $payload['payload']);
        self::assertArrayNotHasKey('client_contact_email', $payload['payload']);
    }

    public function testOptionalContactFieldsSentWhenPresent(): void
    {
        $payload = $this->builder()->build($this->ticket(function(SupportTicket $t): void {
            $t->orderData = ['contactName' => 'Олег', 'contactPhone' => '+7 999 123', 'contactEmail' => 'o@x.ru'];
        }));
        self::assertSame('Олег', $payload['payload']['client_contact_name']);
        self::assertSame('+7 999 123', $payload['payload']['client_contact_phone']);
        self::assertSame('o@x.ru', $payload['payload']['client_contact_email']);
    }

    public function testOrderItemIdsExtractedFromSelectedItems(): void
    {
        $payload = $this->builder()->build($this->ticket(function(SupportTicket $t): void {
            $t->orderData = ['selectedItems' => ['50485_product', '59611_product', '12345_orderitem']];
        }));
        self::assertSame([50485, 59611, 12345], $payload['payload']['order_item_ids']);
    }

    public function testOrderItemIdsOmittedWhenEmpty(): void
    {
        $payload = $this->builder()->build($this->ticket(fn(SupportTicket $t) => $t->orderData = []));
        self::assertArrayNotHasKey('order_item_ids', $payload['payload']);
    }

    public function testMalformedSelectedItemsAreSkipped(): void
    {
        $payload = $this->builder()->build($this->ticket(function(SupportTicket $t): void {
            $t->orderData = ['selectedItems' => ['50485_product', 'not-a-number', '', '99_x']];
        }));
        self::assertSame([50485, 99], $payload['payload']['order_item_ids']);
    }

    public function testAttachmentIdsAttachedWhenProvided(): void
    {
        $payload = $this->builder()->build($this->ticket(), ['fil_a', 'fil_b']);
        self::assertSame(['fil_a', 'fil_b'], $payload['payload']['attachment_ids']);
    }

    public function testNonArrayOrderDataDoesntCrash(): void
    {
        $payload = $this->builder()->build($this->ticket(fn(SupportTicket $t) => $t->orderData = null));
        self::assertSame(4242, $payload['payload']['id']);
    }
}
