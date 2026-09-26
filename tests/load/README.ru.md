# Нагрузочный smoke-тест

*[English version](README.md)*

Ограниченный k6-сценарий проверяет публичный gateway, Ed25519 authentication,
tenant authorization, чтение Customer и correlation propagation при разгоне
до 25 VU. Тест падает при 1% ошибок, p95 выше 500 мс или p99 выше 1 с. Это
воспроизводимый regression gate, а не обещание capacity для любого железа.

Запустите платформу через `make up`, дождитесь readiness и выполните:

```bash
make test-load
```

Цель можно заменить: `BASE_URL=https://staging.example.com make test-load`.
Failure/resilience-дополнение находится в `tests/resilience/` и покрывает
duplicate delivery, outbox recovery, broker outage и crash-before-ack.
