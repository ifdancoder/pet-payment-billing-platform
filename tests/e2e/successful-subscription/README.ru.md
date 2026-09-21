# E2E: successful subscription

*[English version](README.md)*

Создаёт merchant, customer, product, price и subscription через public HTTP. Цепочка событий создаёт и оплачивает invoice, активирует subscription и доставляет notification.

- В стеке работают все семь сервисов.
- Тест не публикует события и не изменяет базы сервисов напрямую.

## Запуск

```bash
cd tests/e2e/successful-subscription
composer install
docker compose up -d --build
composer test
docker compose down --volumes
```
