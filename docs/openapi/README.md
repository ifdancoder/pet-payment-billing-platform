# OpenAPI contract

*[Русская версия](README.ru.md)*

[`openapi.yaml`](openapi.yaml) is the canonical OpenAPI 3.1 contract for
the platform's currently implemented public HTTP API through the gateway.
Its paths use the public `/v1/...` form; the gateway rewrites those paths
to each service's internal `/api/v1/...` routes.

The contract is derived from Laravel route files, Form Request validation,
JSON Resources, exception renderers, and the Nginx routing table. It includes
registration/login/refresh/logout, API-key exchange, membership management,
API-key lifecycle, and every tenant-scoped business endpoint. `bearerAuth`
matches the Ed25519 access-token middleware; only health and `/v1/auth/*`
bootstrap operations are public.

## Validation

Run `make test-docs`: it validates YAML/references/operation IDs and proves
bidirectional parity between OpenAPI operations and all Laravel route tables.
