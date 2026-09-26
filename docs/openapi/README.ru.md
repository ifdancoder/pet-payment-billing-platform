# OpenAPI-контракт

*[English version](README.md)*

[`openapi.yaml`](openapi.yaml) — канонический контракт OpenAPI 3.1 для
реализованного публичного HTTP API платформы через gateway. В paths
используется публичная форма `/v1/...`; gateway преобразует эти пути во
внутренние маршруты сервисов `/api/v1/...`.

Контракт составлен по Laravel-маршрутам, Form Request, JSON Resources,
exception renderers и таблице Nginx. Он включает register/login/refresh/logout,
обмен API key, управление memberships и API keys, а также все tenant-scoped
business endpoints. `bearerAuth` соответствует middleware с Ed25519 access
token; публичны только health и bootstrap-операции `/v1/auth/*`.

## Проверка

Запустите `make test-docs`: команда проверяет YAML, ссылки и operation IDs,
а также двустороннее совпадение операций OpenAPI со всеми Laravel routes.
