# External Secrets Operator

*[Русская версия](README.ru.md)*

[External Secrets Operator](https://external-secrets.io/) will pull
secrets from an external store and sync them into native Kubernetes
`Secret` objects, so manifests never carry plaintext credentials.

## Status

Not installed. Backend is not picked yet (Vault, AWS Secrets Manager, GCP
Secret Manager, or equivalent). No Secret payload is committed meanwhile:
`make init` creates a Git-ignored local `.env`, and `make kind-secrets`
provisions the native Secret objects required by the local overlay.

## Planned setup

Install via Helm:

```bash
helm repo add external-secrets https://charts.external-secrets.io
helm install external-secrets external-secrets/external-secrets \
  --namespace external-secrets --create-namespace
```

Then:

- set up a `SecretStore` / `ClusterSecretStore` for whatever backend gets
  chosen
- add `ExternalSecret` resources per service once services and their
  credentials (database, RabbitMQ, payment provider keys) actually exist

Nothing else in this directory, just this README for now.
