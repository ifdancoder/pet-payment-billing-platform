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

Ещё не построено: `overdue-subscription` — нужен платёж, проваливающийся
у *уже Active* подписки (переход в PastDue, а не guard
«Pending остаётся Pending», уже покрытый `failed-payment`), а значит —
сначала засеять настоящий успешный billing-цикл.

Не путать с отдельными `kind`-based Kubernetes platform smoke tests
(инфраструктурные вопросы: роутит ли Ingress, остаётся ли доступным
rolling update — не бизнесовые вопросы).
