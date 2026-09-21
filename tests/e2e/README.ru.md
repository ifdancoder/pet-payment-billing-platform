# End-to-end тесты

Полные бизнес-флоу через все сервисы (от Identity до Notification), с
fake-провайдерами платежей/email вместо настоящих. Ещё не построено —
сначала нужны оставшиеся
[service integration срезы](../integration/) (`billing-to-payment`,
`payment-to-billing`, `billing-to-subscription`), плюс
детерминированный fake-провайдер платежей/уведомлений. План и текущий
статус — в
[`docs/architecture/testing-strategy.ru.md`](../../docs/architecture/testing-strategy.ru.md).

Не путать с отдельными `kind`-based Kubernetes platform smoke tests
(инфраструктурные вопросы: роутит ли Ingress, остаётся ли доступным
rolling update — не бизнесовые вопросы).
