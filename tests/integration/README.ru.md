# Service integration тесты

2-3 реальных сервиса, общающихся через реальный RabbitMQ и реальные
базы данных — по директории на каждую границу, каждая — свой
самостоятельный Docker Compose стек и Pest-проект. Не вся платформа
(это [`tests/e2e/`](../e2e/)) и не один сервис в изоляции (это
собственные `tests/` каждого сервиса). Полную пирамиду и текущий
статус каждого среза см. в
[`docs/architecture/testing-strategy.ru.md`](../../docs/architecture/testing-strategy.ru.md).

- [`subscription-to-billing/`](subscription-to-billing/) — готово, см.
  собственный README.
- [`billing-to-payment/`](billing-to-payment/) — готово, см. собственный
  README.
- [`payment-to-billing/`](payment-to-billing/) — готово, см. собственный
  README. Первый срез, использующий [`../support/`](../support/) вместо
  локальной копии `eventually()`.
