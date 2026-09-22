# End-to-end тесты

*[English version](README.md)*

E2E suites запускают полные бизнес-сценарии через public HTTP endpoints и настоящие асинхронные workers. Payment и email providers заменены детерминированными fakes.

- `successful-subscription/`: начальный платёж активирует подписку и отправляет receipt.
- `failed-payment/`: первичный отказ оставляет invoice Open, payment Failed и subscription Pending.
- `overdue-subscription/`: неудачное продление переводит Active-подписку в PastDue.
