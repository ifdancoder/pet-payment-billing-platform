# kind platform smoke tests

*[Russian version](README.ru.md)*

The Kubernetes-facing test suite from
[`ADR 0004`](../../docs/adr/0004-testing-strategy.md): infrastructure
questions belong on Kubernetes, separately from the business-focused
Docker Compose suites under `tests/integration/`, `tests/e2e/`, and
`tests/resilience/`.

This is a standalone Pest project, but it does not create or destroy a
cluster. It runs against the current `kubectl` context and expects the
local overlay to be deployed already. That deliberate separation keeps
cluster/image provisioning in the existing Kubernetes workflow and
makes the test process itself read-only apart from the one explicit
`billing-api` rolling restart.

## What it proves

1. **Ingress routing:** all seven public path families go through the
   real ingress-nginx controller and gateway to their intended backend;
   an unknown path reaches the gateway's explicit 503 fallback.
2. **Rolling-update availability:** a real `kubectl rollout restart` of
   the two-replica `billing-api` keeps returning 200 through the Ingress
   while both pods are replaced.
3. **Business smoke:** the successful-subscription golden path reaches
   Active and produces its payment receipt through the real Ingress,
   Kubernetes Services/DNS, all seven services, Postgres, and RabbitMQ.

This is intentionally not another full E2E catalog. The first and
second tests ask Kubernetes/platform questions; the third is the one
small business canary proving the deployed system is actually usable.
NetworkPolicy and autoscaling are not claimed here: the local overlay
does not apply them because kindnet does not enforce NetworkPolicy and
the cluster has no metrics-server.

## Running it

Prerequisites:

- the `pet-payment-billing-platform` kind cluster exists;
- `kubectl` points at it;
- `infrastructure/kubernetes/overlays/local` is applied and all
  workloads are ready;
- native Secrets have been provisioned from the ignored local `.env` with
  `make kind-secrets`;
- ingress-nginx is exposed on `localhost:8090`, as configured by
  `infrastructure/kubernetes/kind-cluster.yaml`.

Then:

```bash
cd tests/kind
composer install
composer test
```

The defaults match the local cluster. For another reachable cluster or
port-forward, override them without changing the tests:

```bash
GATEWAY_URL=http://localhost:18090 \
GATEWAY_HOST=api.pet-payment-billing-platform.local \
composer test
```

The suite leaves the shared cluster and its test data in place. Every
business identifier is unique, so repeated runs do not collide.

## The bug the suite found

The first live rolling-update run consistently dropped a request even
though `billing-api` had two replicas, `maxUnavailable: 0`, and a
matching PodDisruptionBudget. Kubernetes can deliver SIGTERM before
Service endpoint removal has propagated, leaving a short window in
which traffic is still routed to the terminating process.

`infrastructure/kubernetes/overlays/local/api-prestop.yaml` adds a
five-second `preStop` drain window to every API deployment. The full
suite then passed live, and the rolling-update test passed again on a
second independent run. The patch is not conceptually local-only; it
lives in the only overlay that exists today and must be carried into a
shared base or every future environment overlay.
