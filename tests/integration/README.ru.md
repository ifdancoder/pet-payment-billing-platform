# Service integration тесты

*[English version](README.md)*

Каждый suite проверяет одну асинхронную границу между запущенными сервисами с реальными PostgreSQL и RabbitMQ.

- `subscription-to-billing/`: создание подписки открывает инвойс.
- `billing-to-payment/`: новый инвойс создаёт и обрабатывает платёж.
- `payment-to-billing/`: успешный платёж переводит инвойс в Paid и публикует `invoice.paid.v1`.
- `billing-to-subscription/`: результат инвойса изменяет состояние подписки.
- `payment-to-notification/`: успешный платёж создаёт и доставляет receipt.

Upstream event может публиковаться напрямую, если его producer находится за пределами проверяемой границы.
