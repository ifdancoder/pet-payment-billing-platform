-- One logical database per service on this test's shared Postgres
-- instance, same DB-per-service convention as everywhere else (see
-- docs/adr/0003-kubernetes-foundation.md).
CREATE DATABASE identity;
CREATE DATABASE customer;
CREATE DATABASE catalog;
CREATE DATABASE subscription;
CREATE DATABASE billing;
CREATE DATABASE payment;
CREATE DATABASE notification;
