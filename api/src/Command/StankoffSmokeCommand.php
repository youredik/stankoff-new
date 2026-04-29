<?php

declare(strict_types=1);

namespace App\Command;

use ApiPlatform\Metadata\Post;
use App\Entity\SupportTicket;
use App\Enum\SupportTicketStatus;
use App\State\Processor\SupportTicketCreateProcessor;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Dev-only smoke command: creates a SupportTicket via the same Processor used by
 * the HTTP endpoint, exercising the full integration pipeline (outbox + dispatch),
 * without going through Keycloak. Used to verify the end-to-end async flow locally
 * against the fake-stankoff server.
 *
 * Run example:
 *   docker compose exec php php bin/console app:stankoff:smoke
 *   docker compose exec php php bin/console app:stankoff:smoke --author='Виктор Карасёв' --order=12345
 */
#[AsCommand(name: 'app:stankoff:smoke', description: 'Create a SupportTicket and trigger the Stankoff integration')]
final class StankoffSmokeCommand extends Command
{
    public function __construct(
        private readonly SupportTicketCreateProcessor $processor,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('subject', null, InputOption::VALUE_OPTIONAL, 'Ticket subject', 'Smoke test ticket')
            ->addOption('author', null, InputOption::VALUE_OPTIONAL, 'authorName', 'Дмитрий Мыслюк')
            ->addOption('order', null, InputOption::VALUE_OPTIONAL, 'orderId', '27209');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $ticket = new SupportTicket();
        $ticket->subject = (string)$input->getOption('subject');
        $ticket->description = 'Smoke description';
        $ticket->authorName = (string)$input->getOption('author');
        $ticket->orderId = (int)$input->getOption('order');
        $ticket->contractor = 'ООО Smoke';
        $ticket->orderData = [
            'selectedItems' => ['50485_product', '59611_product'],
            'contactName' => 'Олег Тестовый',
            'contactPhone' => '+7(999)123-45-67',
            'contactEmail' => 'oleg@example.ru',
        ];
        // status / createdAt are set inside the processor; passing dummy Operation.
        $op = new Post();
        $result = $this->processor->process($ticket, $op);

        $output->writeln(sprintf('<info>Created ticket id=%d. Watch the worker logs and the outbox row.</info>', $result->getId()));

        return Command::SUCCESS;
    }
}
