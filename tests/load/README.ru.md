# Нагрузочный smoke-тест

*[English version](README.md)*

Ограниченный k6-сценарий против одноразового full-platform Compose stack. Под лёгкой конкурентной нагрузкой выполняет регистрацию, tenant setup, создание catalog и subscription.

- Запускайте `make test-load` из корня репозитория.
- Target использует gateway port `18080` и после прогона удаляет containers и volumes.
- Это smoke check, а не capacity benchmark или SLO claim.
