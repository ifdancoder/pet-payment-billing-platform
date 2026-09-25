# External Secrets Operator

*[Русская версия](README.ru.md)*

No External Secrets resources are defined yet, and no backend has been selected.
Local Secrets are generated from the ignored `.env` by `make kind-secrets`.
Shared environments must provide the same Secret names without committing
credentials.

Install the operator when a backend is selected:

```bash
helm repo add external-secrets https://charts.external-secrets.io
helm upgrade --install external-secrets external-secrets/external-secrets \
  --namespace external-secrets --create-namespace
```

Then add a `SecretStore` or `ClusterSecretStore` and service-specific
`ExternalSecret` resources.
