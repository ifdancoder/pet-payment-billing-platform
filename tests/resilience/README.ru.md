# Resilience тесты

*[English version](README.md)*

Failure-сценарии, не бизнес-сценарии: повторная доставка, падение
консьюмера посреди обработки сообщения, недоступность RabbitMQ,
восстановление Outbox после падения до публикации. Каждый доказывает
одно конкретное утверждение архитектуры платформы (Outbox/Inbox,
идемпотентные консьюмеры) — что оно реально переживает тот failure,
для которого задумано, а не просто что работает happy path. См.
[`docs/architecture/testing-strategy.ru.md`](../../docs/architecture/testing-strategy.ru.md).

- [`duplicate-delivery/`](duplicate-delivery/) — готово, см. собственный
  README. Один и тот же `event_id`, опубликованный дважды; доказывает,
  что Inbox у Billing реально останавливает второй от создания
  дублирующего Invoice, а не просто что в коде есть вызов
  `recordIfNew()`.
- [`outbox-recovery/`](outbox-recovery/) — готово, см. собственный
  README. Останавливает `billing-outbox` посреди сценария (тест сам
  управляет Docker), создаёт Invoice, пока он не работает, затем
  доказывает, что пропущенная строка доходит до wire, как только он
  снова запущен — не потеряна, не проведена заново с нуля.

Ещё не построено: `consumer-crash/`, `rabbitmq-outage/`.
