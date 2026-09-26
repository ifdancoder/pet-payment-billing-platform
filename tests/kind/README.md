# kind platform smoke tests

*[Русская версия](README.ru.md)*

Runs black-box checks against an existing Kubernetes deployment.

- Every public ingress route reaches its service.
- `billing-api` remains available during an intentional rolling restart.
- A successful subscription flow completes through the deployed workers.

Run `make test-kind`. The default context is `kind-pet-payment-billing-platform`; override it with `KIND_CONTEXT=<context>`. The suite changes cluster state by restarting `billing-api`.
