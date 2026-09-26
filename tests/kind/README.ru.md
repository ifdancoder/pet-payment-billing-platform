# kind platform smoke tests

*[English version](README.md)*

Запускает black-box проверки существующего Kubernetes deployment.

- Каждый public ingress route достигает своего сервиса.
- `billing-api` остаётся доступен во время намеренного rolling restart.
- Успешный сценарий подписки проходит через развёрнутые workers.

Запуск: `make test-kind`. Контекст по умолчанию: `kind-pet-payment-billing-platform`; для замены передайте `KIND_CONTEXT=<context>`. Suite изменяет состояние кластера, перезапуская `billing-api`.
