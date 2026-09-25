# OpenAPI contract

*[Русская версия](README.ru.md)*

[`openapi.yaml`](openapi.yaml) is the canonical OpenAPI 3.1 contract for
the platform's currently implemented public HTTP API through the gateway.
Its paths use the public `/v1/...` form; the gateway rewrites those paths
to each service's internal `/api/v1/...` routes.

The contract is derived from the Laravel route files, Form Request
validation rules, JSON Resources, exception renderers, and the Nginx
gateway table. It intentionally excludes the reserved but unimplemented
`/v1/auth/*`, membership, and API-key routes.

No security scheme is declared because authentication and authorization
are not wired into the current HTTP routes. Adding a scheme before the
middleware exists would make the contract stronger than the implementation.

## Validation

The file is valid YAML and contains unique `operationId` values for every
operation. Use any OpenAPI 3.1-compatible validator or viewer, for example
Redocly CLI or Swagger Editor, for full schema validation and rendering.
