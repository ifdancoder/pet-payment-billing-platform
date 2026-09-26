# Load smoke test

*[Русская версия](README.ru.md)*

This bounded k6 scenario validates the public gateway, Ed25519 authentication,
tenant authorization, Customer reads and correlation propagation under a
25-VU ramp. It fails when errors reach 1%, p95 exceeds 500 ms, or p99 exceeds
1 s. It is a repeatable regression gate, not a capacity claim for arbitrary
hardware.

Start the platform with `make up`, wait for readiness, then run:

```bash
make test-load
```

Override the target with `BASE_URL=https://staging.example.com make test-load`.
The failure/resilience complement lives under `tests/resilience/` and covers
duplicate delivery, outbox recovery, broker outage and crash-before-ack.
