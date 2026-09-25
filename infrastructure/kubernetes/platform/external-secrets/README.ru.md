# External Secrets Operator

*[English version](README.md)*

[External Secrets Operator](https://external-secrets.io/) получает
секреты из внешнего хранилища и синхронизирует их в нативные Kubernetes-
объекты `Secret`, чтобы манифесты никогда не содержали учётные данные в
открытом виде.

## Состояние

Не установлен. Backend пока также не выбран: Vault, AWS Secrets
Manager, GCP Secret Manager или другой вариант. Локальная разработка
по-прежнему использует обычные `.env`-файлы; см. `.env.example` в корне
репозитория.

## План установки

Установить через Helm:

```bash
helm repo add external-secrets https://charts.external-secrets.io
helm install external-secrets external-secrets/external-secrets \
  --namespace external-secrets --create-namespace
```

Затем:

- настроить `SecretStore` / `ClusterSecretStore` для выбранного backend;
- добавить ресурсы `ExternalSecret` для каждого сервиса, когда появятся
  сами сервисы и их учётные данные для базы данных, RabbitMQ и платёжных
  провайдеров.

Других файлов в этом каталоге пока нет — только этот README.
