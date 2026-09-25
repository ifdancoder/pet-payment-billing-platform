# External Secrets Operator

*[English version](README.md)*

External Secrets resources пока не определены, backend не выбран. Локальные
Secrets создаются из игнорируемого `.env` командой `make kind-secrets`. Shared
environments должны создавать Secret objects с теми же именами без хранения
credentials в Git.

После выбора backend установите operator:

```bash
helm repo add external-secrets https://charts.external-secrets.io
helm upgrade --install external-secrets external-secrets/external-secrets \
  --namespace external-secrets --create-namespace
```

Затем добавьте `SecretStore` или `ClusterSecretStore` и service-specific
`ExternalSecret` resources.
