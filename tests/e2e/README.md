# End-to-end tests

*[Русская версия](README.ru.md)*

E2E suites drive complete business workflows through public HTTP endpoints and real asynchronous workers. Payment and email providers are deterministic fakes.

- `successful-subscription/`: the initial payment activates the subscription and sends a receipt.
- `failed-payment/`: an initial decline leaves the invoice Open, payment Failed, and subscription Pending.
- `overdue-subscription/`: a failed renewal moves an Active subscription to PastDue.
