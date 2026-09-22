-- A Component test boots exactly one service, so exactly one database
-- — unlike every tests/integration/*/ and tests/e2e/*/ stack, which
-- run several services against their own DB-per-service instance.
CREATE DATABASE subscription;
