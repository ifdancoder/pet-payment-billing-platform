# OpenAPI-контракт

*[English version](README.md)*

`openapi.yaml` является каноническим контрактом OpenAPI 3.1 для публичного API
gateway. Публичные пути `/v1` соответствуют внутренним маршрутам сервисов
`/api/v1`. Схемы составлены по реализованным requests, resources и exception
responses.

Команда `make test-docs` проверяет YAML references, operation IDs и двустороннее
соответствие операций OpenAPI зарегистрированным Laravel routes.
