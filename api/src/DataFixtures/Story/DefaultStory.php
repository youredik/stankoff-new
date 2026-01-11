<?php

declare(strict_types=1);

namespace App\DataFixtures\Story;

use App\DataFixtures\Factory\SupportTicketFactory;
use App\DataFixtures\Factory\UserFactory;
use App\Entity\SupportTicketComment;
use App\Enum\SupportTicketClosingReason;
use App\Enum\SupportTicketStatus;
use Doctrine\Persistence\ObjectManager;
use Zenstruck\Foundry\Story;

final class DefaultStory extends Story
{
    public function build(): void
    {
        // Create default user
        UserFactory::createOne([
            'email' => 'john.doe@example.com',
            'firstName' => 'John',
            'lastName' => 'Doe',
        ]);

        // Create admin user
        UserFactory::createOneAdmin();

        // Create additional users (total 5 users)
        UserFactory::createMany(3);

        // Create support tickets
        $tickets = SupportTicketFactory::createMany(10);

        // Create comments for each ticket (15-20 comments per ticket)
        $manager = self::factory()->getManager();
        foreach ($tickets as $ticket) {
            $numComments = random_int(15, 20);

            // First comment always NEW
            $this->createComment($manager, $ticket, SupportTicketStatus::NEW, null);

            // Remaining comments with random statuses
            for ($i = 1; $i < $numComments; $i++) {
                $status = self::faker()->randomElement(SupportTicketStatus::cases());
                $closingReason = $status === SupportTicketStatus::COMPLETED
                    ? self::faker()->randomElement(SupportTicketClosingReason::cases())
                    : null;
                $this->createComment($manager, $ticket, $status, $closingReason);
            }
        }
    }

    private function createComment(ObjectManager $manager, $ticket, SupportTicketStatus $status, ?SupportTicketClosingReason $closingReason): void
    {
        $comment = new SupportTicketComment();
        $comment->comment = self::faker()->paragraph(2, 'ru_RU');
        $comment->status = $status;
        $comment->closingReason = $closingReason;
        $comment->createdAt = self::faker()->dateTimeBetween('-1 year', 'now');
        $comment->supportTicket = $ticket;
        $comment->user = UserFactory::random();

        $manager->persist($comment);
        $manager->flush();
    }
}
