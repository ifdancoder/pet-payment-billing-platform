-- DB-per-service on one shared Postgres instance for local/dev use: a
-- separate logical database per service, not seven physical servers
-- (see docs/adr/0003-kubernetes-foundation.md). Add one line here per
-- service as it gets containerized — this only runs once, against an
-- empty data directory, so an existing cluster won't pick up additions
-- without a volume reset.
CREATE DATABASE customer;
