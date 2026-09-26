# OpenAPI contract

*[Русская версия](README.ru.md)*

`openapi.yaml` is the canonical OpenAPI 3.1 contract for the public gateway API.
Public `/v1` paths map to each service's internal `/api/v1` routes. Schemas are
based on the implemented requests, resources, and exception responses.

Run `make test-docs` to validate YAML references, operation IDs, and bidirectional
parity between OpenAPI operations and registered Laravel routes.
