# Load smoke test

*[Русская версия](README.ru.md)*

A bounded k6 scenario against a disposable full-platform Compose stack. It exercises registration, tenant setup, catalog creation, and subscription creation under light concurrent traffic.

- Run `make test-load` from the repository root.
- The target uses gateway port `18080` and removes its containers and volumes afterward.
- This is a smoke check, not a capacity benchmark or SLO claim.
