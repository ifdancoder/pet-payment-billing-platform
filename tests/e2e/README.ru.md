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

Ещё не построено: failure-path сценарии (`failed-payment`,
`overdue-subscription`) — обоим нужно детерминированное *управление*
исходом fake-провайдера платежей со стороны теста, которого пока нет
(сам fake-провайдер есть и всегда успешен — см. раздел «Fake providers»
документа стратегии тестирования).

Не путать с отдельными `kind`-based Kubernetes platform smoke tests
(инфраструктурные вопросы: роутит ли Ingress, остаётся ли доступным
rolling update — не бизнесовые вопросы).
