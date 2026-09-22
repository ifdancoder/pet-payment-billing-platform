# Component тесты

*[English version](README.md)*

Один реальный сервис, как собственный живой процесс — настоящий
HTTP-сервер, настоящий Postgres, настоящий RabbitMQ — с stub-сервером,
заменяющим всё, с чем он общается по HTTP, вместо настоящих
зависимых сервисов. Не [`tests/integration/`](../integration/) (2-3
*настоящих* сервиса) и не in-process уровни Application/Feature внутри
собственного `tests/` каждого сервиса (нет живого процесса, нет
настоящего RabbitMQ). См.
[`docs/architecture/testing-strategy.ru.md`](../../docs/architecture/testing-strategy.ru.md)
для полной пирамиды и текущего статуса каждого уровня.

Каждый сервис, которому это нужно, получает здесь собственный каталог,
собственный отдельный стек Docker Compose и Pest-проект — та же форма,
что и у [`tests/integration/`](../integration/),
[`tests/e2e/`](../e2e/) и [`tests/resilience/`](../resilience/),
поскольку Component нужен реально поднятый HTTP-сервер и реальный
брокер, которые in-process прогон Laravel-тестов (Feature-тесты) дать
не может.

- [`subscription-service/`](subscription-service/) — готово, см.
  собственный README. Первый Component-тест: WireMock-stub заменяет
  customer-service и catalog-service, две собственные синхронные
  HTTP-зависимости subscription-service.
- [`notification-service/`](notification-service/) — готово, см.
  собственный README. Второй Component-тест, и единственный другой
  сервис платформы с синхронной исходящей HTTP-зависимостью, которую
  стоило застабить (customer-service — для получателя email-receipt) —
  в отличие от `subscription-service`, он ничего не публикует, так что
  этот тест проверяет только consume-сторону RabbitMQ и delivery
  worker.

Для остальных пяти сервисов ещё не построено — ни у одного из них нет
исходящей HTTP-зависимости или guard-условия, которому пригодилась бы
изоляция так же, как этим двум; см. «Следующий срез» в документе
стратегии тестирования.
