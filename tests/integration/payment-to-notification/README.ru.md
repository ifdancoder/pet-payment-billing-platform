# Service integration: Payment to Notification

*[English version](README.md)*

Публикует `payment.succeeded.v1` напрямую и проверяет настоящие consumer Notification, HTTP lookup Customer и delivery worker.

- Известный customer получает один receipt.
- Для неизвестного customer notification не создаётся. Проверка отсутствия использует ограниченный wait, потому что `eventually()` не может доказать отсутствие.

## Запуск

```bash
cd tests/integration/payment-to-notification
composer install
docker compose up -d --build
composer test
docker compose down --volumes
```
