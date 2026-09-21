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
- [`billing-to-subscription/`](billing-to-subscription/) — готово, см.
  собственный README. Закрыл последний срез, нужный `tests/e2e/` перед
  сборкой его первого сценария.
- [`payment-to-notification/`](payment-to-notification/) — готово, см.
  собственный README. Пятый и последний из срезов границ событий,
  выделенных в пирамиде; включает негативный тест, доказывающий
  отсутствие (нет notification для неизвестного customer), а не только
  переход состояния.
