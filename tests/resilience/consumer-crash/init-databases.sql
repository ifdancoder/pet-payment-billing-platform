-- One logical database per service on this test's shared Postgres
-- instance, same DB-per-service convention as everywhere else (see
-- docs/adr/0003-kubernetes-foundation.md).
CREATE DATABASE billing;
