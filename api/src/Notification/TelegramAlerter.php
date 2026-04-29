<?php

declare(strict_types=1);

namespace App\Notification;

use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Posts plain notifications to a Telegram chat via the bot Send API.
 *
 * Design constraints:
 *   - Failures NEVER throw. A TG outage must not break ticket creation or the
 *     dedupe poller. Internal exceptions are logged at warning level.
 *   - Disabled by default (TELEGRAM_ALERTS_ENABLED=false). Local/test envs can
 *     keep it off without setting fake credentials.
 *   - Short timeout (3s) — we don't want a slow TG endpoint to wedge a worker.
 *
 * Scope today: ticket created, webhook delivered, webhook permanently failed,
 * dedupe lift. Add new call sites by injecting the service and calling notify().
 */
final class TelegramAlerter
{
    public function __construct(
        #[Autowire(env: 'TELEGRAM_BOT_TOKEN')] private readonly string $botToken,
        #[Autowire(env: 'TELEGRAM_CHAT_ID')] private readonly string $chatId,
        #[Autowire(env: 'bool:TELEGRAM_ALERTS_ENABLED')] private readonly bool $enabled,
        private readonly HttpClientInterface $httpClient,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * @param array<string,scalar|null> $fields key→value lines to render under the title
     */
    public function notify(string $title, array $fields = []): void
    {
        if (!$this->enabled || $this->botToken === '' || $this->chatId === '') {
            return;
        }

        try {
            $this->httpClient->request('POST', sprintf('https://api.telegram.org/bot%s/sendMessage', $this->botToken), [
                'json' => [
                    'chat_id' => $this->chatId,
                    'text' => $this->format($title, $fields),
                    'parse_mode' => 'HTML',
                    'disable_web_page_preview' => true,
                ],
                'timeout' => 3,
                'max_duration' => 3,
            ]);
        } catch (\Throwable $e) {
            $this->logger->warning('telegram alert failed (suppressed)', [
                'title' => $title,
                'exception' => $e,
            ]);
        }
    }

    /**
     * @param array<string,scalar|null> $fields
     */
    private function format(string $title, array $fields): string
    {
        $lines = ['<b>' . self::esc($title) . '</b>'];
        foreach ($fields as $k => $v) {
            if ($v === null || $v === '') {
                continue;
            }
            $lines[] = self::esc((string) $k) . ': <code>' . self::esc((string) $v) . '</code>';
        }
        return implode("\n", $lines);
    }

    private static function esc(string $s): string
    {
        return htmlspecialchars($s, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
}
