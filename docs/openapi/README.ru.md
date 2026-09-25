# OpenAPI-контракт

*[English version](README.md)*

[`openapi.yaml`](openapi.yaml) — канонический контракт OpenAPI 3.1 для
реализованного публичного HTTP API платформы через gateway. В paths
используется публичная форма `/v1/...`; gateway преобразует эти пути во
внутренние маршруты сервисов `/api/v1/...`.

Контракт составлен по Laravel-маршрутам, правилам валидации Form Request,
JSON Resources, exception renderers и таблице маршрутизации Nginx. Пока не
реализованные, но зарезервированные маршруты `/v1/auth/*`, membership и
API keys намеренно не включены.

Security scheme не объявлена, потому что authentication и authorization
ещё не подключены к текущим HTTP-маршрутам. Если добавить схему раньше
middleware, контракт будет обещать больше, чем реализовано.

## Проверка

Файл является корректным YAML и содержит уникальные `operationId` для всех
операций. Для полной проверки схемы и визуализации можно использовать любой
валидатор OpenAPI 3.1, например Redocly CLI или Swagger Editor.
