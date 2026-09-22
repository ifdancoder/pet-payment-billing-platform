# E2E: overdue subscription

*[English version](README.md)*

Третий полный end-to-end сценарий и production-фича recurring billing,
без которой его нельзя было построить честно. Подписка сначала полностью
проходит начальный цикл и становится Active; затем scheduler открывает
Invoice следующего цикла, renewal-платёж отклоняется, и настоящая цепочка
событий переводит подписку в PastDue.

Тест ничего не публикует напрямую и не меняет базы данных. Он вызывает
ту же команду `subscriptions:renew`, которую запускает Kubernetes
CronJob `subscription-renewals`, используя операционный параметр
`--as-of`, чтобы перейти границу однодневного периода без ожидания суток.

## Проверяемый production-путь

1. Subscription хранит `current_period_start`, `current_period_end` и
   атомарный guard `renewal_pending`.
2. `subscriptions:renew` блокирует ограниченный batch Active-подписок с
   истёкшим периодом, продвигает ровно один период и записывает
   `subscription.renewal_due.v1` в Outbox в той же транзакции.
3. Billing потребляет событие и создаёт Invoice следующего цикла.
4. `invoice.created.v1` передаёт в Payment
   `billing_reason=subscription_cycle`.
5. Зарезервированная renewal-only сумма fake-провайдера успешна для
   `subscription_create`, но отклоняется для `subscription_cycle`.
6. Payment → Billing → Subscription производит
   `payment.failed.v1` → `invoice.payment_failed.v1` → PastDue.

Финальный вызов scheduler переводит часы в 2030 год и всё равно ставит
ноль renewals, доказывая, что PastDue-подписка не создаёт третий Invoice.
Отдельно pending guard не даёт параллельным запускам scheduler открыть
один цикл дважды.

## Стек и запуск

Compose-файл через Compose `include` переиспользует точную топологию из
`../successful-subscription/docker-compose.yaml`; сценарий не
поддерживает третью копию того же семисервисного стека.

```bash
cd tests/e2e/overdue-subscription
composer install
docker compose up -d --build
composer test
docker compose down --volumes
```

## Проверено вживую

Финальный прогон на пересобранных образах прошёл со 176 assertions за
12,27 секунды. Логи контейнеров
показали обе настоящие доставки `invoice.created.v1`, один успешный
начальный платёж, один неудачный renewal-платёж, failure relay Billing и
оба результата на стороне Subscription. Ни shortcut API, ни прямой
AMQP publish не использовались.
