# Топология RabbitMQ

*[English version](README.md)*

Здесь появятся ресурсы [RabbitMQ Topology Operator](https://www.rabbitmq.com/kubernetes/operator/install-topology-operator)
(`Queue`, `Exchange`, `Binding`, `Vhost`), когда:

- кластер RabbitMQ (`../cluster.yaml`) действительно будет развёрнут;
- появятся сервисы приложений и станет понятно, какие очереди, exchanges
  и bindings им нужны.

Пока ни одно из условий не выполнено (см. раздел «Статус» в корневом
README), поэтому объявлять здесь нечего. Ожидаемая структура после
появления сервисов:

```text
topology/
├── kustomization.yaml
├── vhosts.yaml
├── <service-name>-exchanges.yaml
├── <service-name>-queues.yaml
└── <service-name>-bindings.yaml
```
