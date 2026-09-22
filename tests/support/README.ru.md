# Общая тестовая инфраструктура

*[English version](README.md)*

Локальный Composer package для black-box suites репозитория.

- `eventually()`: опрашивает assertion до успеха или timeout.
- `AmqpTestClient`: объявляет test exchange, публикует события и читает приватные test queues.
- `DockerCompose`: останавливает, запускает, убивает и проверяет сервисы из resilience tests.
- `TestAccessToken`: создаёт подписанные access tokens для тестовых identities.

Suites подключают package через Composer path repository и загружают `tests/support/service-bootstrap.php`.
