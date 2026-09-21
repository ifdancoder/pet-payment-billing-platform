# Resilience тесты

*[English version](README.md)*

Каждый suite изолирует один failure mode в messaging path.

- `duplicate-delivery/`: inbox deduplication предотвращает второй invoice.
- `outbox-recovery/`: перезапущенный relay публикует строки, записанные во время остановки.
- `rabbitmq-outage/`: API write проходит при недоступном RabbitMQ, затем цепочка догоняет.
- `consumer-crash/`: падение после commit и до acknowledgement приводит к redelivery без дублирования эффектов.
