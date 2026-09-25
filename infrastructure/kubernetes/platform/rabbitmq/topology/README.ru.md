# Топология RabbitMQ

*[English version](README.md)*

В local overlay и корневом Compose stack очереди и bindings объявляют consumer
processes. Этот каталог зарезервирован для ресурсов RabbitMQ Topology Operator
в shared environments.

Ресурсы `Queue`, `Exchange`, `Binding` и `Vhost` пока не определены. Добавляйте
их при замене базового RabbitMQ Deployment на operator-backed `RabbitmqCluster`;
имена должны соответствовать ADR 0002 и consumer commands.
