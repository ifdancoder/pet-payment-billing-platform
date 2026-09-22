# End-to-end тесты

*[English version](README.md)*

Полные бизнес-флоу через все сервисы (от Identity до Notification), с
fake-провайдерами платежей/email вместо настоящих. Полный план и
текущий статус — в
[`docs/architecture/testing-strategy.ru.md`](../../docs/architecture/testing-strategy.ru.md).

- [`successful-subscription/`](successful-subscription/) — готово, см.
  собственный README. Happy path: Merchant → Customer → Product/Price →
  Subscription → Invoice → Payment → Subscription Active →
  Notification.
- [`failed-payment/`](failed-payment/) — готово, см. собственный README.
  Первый failure-path сценарий: те же семь сервисов, но сумма Price —
  зарезервированное decline-триггер значение `FakePaymentGateway`, так
  что списание гарантированно отклоняется. Доказывает, что Invoice
  остаётся Open, Payment становится Failed с настоящим кодом отказа
  провайдера, а Subscription — так и не дошедший до Active — остаётся
  Pending, а не PastDue.

Ещё не построено: `overdue-subscription` — заблокирован настоящей
production-фичей, которой у платформы нет, а не тестовой
инфраструктурой: нет scheduled job, создающего Invoice *второго*
billing-цикла для Active-подписки, так что нет способа дойти до
состояния «была Active, теперь просрочена», чтобы его протестировать.
Переход в PastDue, который она бы проверяла, и так уже полностью
покрыт вторым тестом
[`tests/integration/billing-to-subscription/`](../integration/billing-to-subscription/).
См. «Следующий срез» в документе стратегии тестирования.

Не путать с отдельными `kind`-based Kubernetes platform smoke tests
(инфраструктурные вопросы: роутит ли Ingress, остаётся ли доступным
rolling update — не бизнесовые вопросы).
