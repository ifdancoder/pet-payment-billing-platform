# Component tests

*[Русская версия](README.ru.md)*

One real service, as its own live process — real HTTP server, real
Postgres, real RabbitMQ — with a stub server standing in for whatever
it talks to over HTTP, rather than the real dependent services. Not
[`tests/integration/`](../integration/) (2-3 *real* services) and not
the in-process Application/Feature layers inside each service's own
`tests/` (no live process, no real RabbitMQ). See
[`docs/architecture/testing-strategy.md`](../../docs/architecture/testing-strategy.md)
for the full pyramid and current status of every layer.

Each service that needs one gets its own directory here, its own
standalone Docker Compose stack and Pest project — the same shape as
[`tests/integration/`](../integration/), [`tests/e2e/`](../e2e/) and
[`tests/resilience/`](../resilience/), because Component needs a real
booted HTTP server and a real broker, which an in-process Laravel test
run (Feature tests) can't provide.

- [`subscription-service/`](subscription-service/) — done, see its own
  README. The first Component test: a WireMock stub stands in for
  customer-service and catalog-service, subscription-service's own two
  synchronous HTTP dependencies.

Not built yet for the other six services — see "Building the next
slice" in the testing strategy doc for which ones would actually
benefit from one.
