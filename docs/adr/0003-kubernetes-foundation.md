# 3. Kubernetes foundation: runtime, manifests, health checks

*[Русская версия](0003-kubernetes-foundation.ru.md)*

## Status

Accepted

## Context

None of the seven services have a Docker image or a Kubernetes
manifest yet. Before writing twenty-plus Deployments across seven
services, the small set of decisions that every one of them will copy
needs to be made once, deliberately, against a real working example —
not decided per-service as each one gets containerized (the same
"decide once, don't rediscover it seven times" reasoning as ADR 0002
for messaging).

The first working example is customer-service: the simplest service
(one Deployment, no consumer, no worker, no CronJob), used here to
prove the pattern before every other service just repeats it.

Critically: **one Docker image per service is not one Kubernetes Pod
per service.** `billing-service:<sha>` is one image; it runs as an API
Deployment, a Consumer Deployment, and an Outbox Deployment, each the
same image with a different container command. Only customer-service
and catalog-service — no consumer, no outbox — are simple enough to be
a single Deployment for now.

## Decision

### Runtime: FrankenPHP, classic mode, no Octane

Each service's container runs [FrankenPHP](https://frankenphp.dev/)
(`dunglas/frankenphp:1-php8.5-alpine`) rather than the traditional
php-fpm + nginx pair: one process, one binary, built-in graceful
shutdown handling (verified: `SIGTERM` → clean exit within the
container's own grace period, `exit_code: 0`, no lingering connections)
without writing any shutdown-handling code ourselves.

Classic mode (`frankenphp php-server --root /app/public`) — the same
one-request-per-process model as php-fpm, no persistent application
state between requests. **Not** Octane/worker mode: that keeps the
Laravel application booted in memory across requests for throughput,
which also means service containers/singletons that assume a
short-lived request can leak state across requests if they're not
written for it. Nothing in this codebase has been audited for that.
Worker mode is a later optimization once classic mode is proven, not a
requirement to get a first service running.

### Image build: multi-stage, `composer:2`'s binary copied into `base`

Composer dependencies are installed inside a stage built `FROM base`
(the same FrankenPHP image the runtime stage uses), not the official
`composer:2` image — `composer:2` doesn't have `ext-sockets` compiled
in, and `php-amqplib`'s platform check fails without it. The fix is
`COPY --from=composer:2 /usr/bin/composer /usr/bin/composer` onto
`base` instead: one consistent extension set for both "does the
dependency graph even resolve" and the actual runtime, rather than two
different PHP builds disagreeing about what extensions exist.

Runs as a non-root `laravel` user (uid 1000) owning
`storage/`, `bootstrap/cache/` (Laravel's own writable paths) and
`/data`, `/config` (FrankenPHP/Caddy's own state directories — left
root-owned, the container logs permission-denied warnings on every
boot; harmless in practice but not a clean non-root setup).

### Health checks: three endpoints, three different questions

`routes/health.php`, loaded outside `app/Presentation` (a framework/
infra concern, the same as Laravel's own `/up`, not a versioned
business route):

| Endpoint | Checks | Kubernetes probe |
| --- | --- | --- |
| `/health/startup` | Nothing but "the process is answering HTTP" | `startupProbe` |
| `/health/live` | Same — no external dependency | `livenessProbe` |
| `/health/ready` | `DB::connection()->getPdo()` | `readinessProbe` |

Liveness deliberately does **not** check the database. A liveness
probe failing tells Kubernetes "kill and restart this container" — if
that were wired to database reachability, a PostgreSQL outage would
have Kubernetes restart every pod of every service repeatedly, which
fixes nothing and adds restart-storm noise on top of an already-down
dependency. Readiness failing tells Kubernetes "stop routing traffic
here" instead, which is the correct response to "I can't currently do
useful work" — no restart needed, traffic just moves to a pod that can
still serve (or none, and the caller sees the outage directly rather
than a pod flapping).

### Manifests: Kustomize, not Helm, for application workloads

`infrastructure/kubernetes/base/<service>/` holds the bare manifests
per service (`deployment.yaml`, `service.yaml`, `configmap.yaml`,
`secret.yaml`, `kustomization.yaml`); `infrastructure/kubernetes/
overlays/{local,staging,production}/` layers environment-specific
values (replica count, resource sizing, image tag) on top via
Kustomize patches — no templating language, no values-schema to
maintain in parallel with the base manifests themselves.

Helm stays reserved for third-party infrastructure this repo doesn't
author (already the case for RabbitMQ, Prometheus, Grafana, Loki,
Tempo under `platform/` — see their own `kustomization.yaml`s wrapping
upstream Helm charts). Application manifests we write ourselves don't
get a second, parallel packaging format.

### Namespacing: per-environment, not per-service

One namespace per environment (`pet-payment-billing-platform` locally
today; `billing-staging` / `billing-production` later), not one per
service. Seven services × three environments × separate RBAC/
NetworkPolicy/quota per namespace is operational ceremony this
platform doesn't get anything for — services are distinguished by
labels (`app.kubernetes.io/name`) within one namespace, which is
already how `kubectl`, Prometheus, and NetworkPolicy selectors expect
to work.

## Consequences

**Easier:**
- Every other service's Dockerfile is now "copy customer-service's,
  change nothing" for the API image, and "copy it, change the `CMD`"
  for a worker/outbox image of the same service.
- The health-check split means a database outage degrades gracefully
  (pods stop receiving traffic) instead of catastrophically (every pod
  in the cluster restarting in a loop while the database is still
  down).
- Kustomize overlays keep environment differences in one place instead
  of scattered across duplicated YAML per environment.

**Harder / follow-up work:**
- `/data` and `/config` inside the container stay root-owned — a
  cosmetic gap (warnings, not failures) worth closing later, not
  blocking this milestone.
- Classic mode leaves throughput on the table relative to Octane/
  worker mode; revisit once there's an actual load-testing reason to.
- No image has been scanned, signed, or pinned by digest yet — tags
  only. That's Phase 11 (CI/CD) territory, not this one.
- This ADR's decisions were validated against customer-service only.
  The next real test is the RabbitMQ vertical slice
  (Subscription → Outbox → RabbitMQ → Billing → Inbox → Invoice →
  Outbox) fully in-cluster — that's where Deployment-vs-worker-vs-
  CronJob, graceful consumer shutdown, and migration Jobs actually get
  exercised together, not just a single stateless API.
