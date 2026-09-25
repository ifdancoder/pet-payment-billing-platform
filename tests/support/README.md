# Shared test support

*[Русская версия](README.ru.md)*

A local Composer package for black-box repository suites.

- `eventually()`: polls an assertion until it passes or times out.
- `AmqpTestClient`: declares the test exchange, publishes events, and reads private test queues.
- `DockerCompose`: stops, starts, kills, and inspects services from resilience tests.
- `TestAccessToken`: creates signed access tokens for test identities.

Suites include this package through a Composer path repository and load `tests/support/service-bootstrap.php`.
