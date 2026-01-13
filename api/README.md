# API для системы поддержки клиентов

Этот API построен на базе Symfony с использованием API Platform и предоставляет функциональность для управления тикетами поддержки клиентов.

## Основные возможности

- Создание и управление тикетами поддержки (SupportTicket)
- Добавление комментариев к тикетам (SupportTicketComment)
- Интеграция с данными заказов
- Аутентификация через OpenID Connect (OIDC)
- Real-time обновления через Mercure
- REST и GraphQL API

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
