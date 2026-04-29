# API для системы поддержки клиентов

Этот API построен на базе Symfony с использованием API Platform и предоставляет функциональность для управления тикетами поддержки клиентов.

## Основные возможности

- Создание и управление тикетами поддержки (SupportTicket)
- Добавление комментариев к тикетам (SupportTicketComment)
- Интеграция с данными заказов
- Аутентификация через OpenID Connect (OIDC)
- Real-time обновления через Mercure
- REST и GraphQL API
- **Асинхронная интеграция с центральной платформой Stankoff** (см. ниже)

## Технологии

- Symfony 7.3
- API Platform 4.2
- Doctrine ORM
- JWT Bundle
- Mercure Bundle
- PHPUnit для тестирования
- PHPStan для статического анализа

## Развертывание

Проект разворачивается с помощью Docker Compose. Запустите:

```bash
docker-compose up -d
```

## Документация API

Документация доступна по адресу `/docs` после запуска сервера.

## Тестирование

```bash
composer test
```

## Разработка

- Используйте PSR-12 и strict_types
- Запускайте тесты перед коммитом
- Следуйте Git flow для ветвления

Для более подробной информации обратитесь к [документации API Platform](https://api-platform.com/docs/distribution).

## Интеграция со Stankoff (preprod.stankoff.ru)

При создании тикета (POST /support_tickets) система асинхронно отправляет
подписанный HMAC-SHA256 webhook в центральную платформу Stankoff через
паттерн **Outbox + Symfony Messenger**.

Полная документация — в [`docs/integrations/stankoff/`](../docs/integrations/stankoff/):

- [`README.md`](../docs/integrations/stankoff/README.md) — обзор и quick start
- [`architecture.md`](../docs/integrations/stankoff/architecture.md) — дизайн и обоснование решений
- [`operations.md`](../docs/integrations/stankoff/operations.md) — runbook для деплоя и эксплуатации
- [`stress-test-findings.md`](../docs/integrations/stankoff/stress-test-findings.md) — результаты стресс-теста (~140 проб)
- [`security.md`](../docs/integrations/stankoff/security.md) — модель угроз, HMAC, PII

**Краткая шпаргалка:**

```bash
# Воркер (отдельный контейнер) консьюмит очередь
docker compose up -d php-worker

# Включение интеграции — два флага в env (по умолчанию OFF):
STANKOFF_INTEGRATION_ENABLED=true     # главный рубильник
STANKOFF_SHADOW_MODE=true             # сначала shadow (без HTTP), потом false

# Локальный E2E через console-команду (без OIDC-токена)
docker compose exec php php bin/console app:stankoff:smoke

# Состояние
docker compose exec database psql -U app -d app \
  -c "SELECT status, count(*) FROM integration_outbox_event GROUP BY status;"
```
