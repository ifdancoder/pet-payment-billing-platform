# 3. Kubernetes foundation: runtime, manifests, health checks

*[Русская версия](0003-kubernetes-foundation.ru.md)*

## Status

Accepted

## Context

All services need the same container runtime, health semantics, manifest layout,
and secret boundary. Worker roles must run independently even when they share an
image with the API.

## Decision

### Runtime and images

Each service builds on `dunglas/frankenphp:1-php8.5-alpine` and runs FrankenPHP
in classic mode. Octane is not installed, so application state does not persist
between requests. The Composer binary is copied into the same PHP base image for
the dependency stage; this keeps required extensions, including `sockets`,
consistent between installation and runtime.

Containers run as uid 1000. Laravel and FrankenPHP writable directories are
owned by that user. One image may run several workloads with different commands,
for example an API Deployment, consumer Deployment, and outbox Deployment.

### Health checks

| Endpoint | Check | Probe |
| --- | --- | --- |
| `/health/startup` | HTTP process responds | startup |
| `/health/live` | HTTP process responds | liveness |
| `/health/ready` | PostgreSQL connection succeeds | readiness |

Liveness does not depend on PostgreSQL. A database outage removes pods from
service endpoints through readiness without causing a restart loop.

### Manifests and secrets

Application manifests use Kustomize. Shared third-party infrastructure may use
upstream Helm charts through Kustomize's Helm integration. Environments use one
namespace each, with services separated by labels and ServiceAccounts.

No Secret payload is committed. Local secrets are generated in the ignored
`.env` and applied with `make kind-secrets`. Shared environments are expected to
provide the same Secret names through External Secrets or an equivalent
operator.

## Consequences

API and worker workloads scale and restart independently while sharing build
artifacts. Switching to a persistent PHP worker model requires an application
state audit. Environment overlays own replica counts, image tags, and resource
sizing. Operator-backed platform components remain optional and require their
controllers to be installed.
