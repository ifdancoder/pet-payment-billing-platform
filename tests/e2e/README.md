# End-to-end tests

Full business flows across every service (Identity through
Notification), with fake payment/email providers instead of real ones.
Not built yet — needs the remaining
[service integration slices](../integration/) (`billing-to-payment`,
`payment-to-billing`, `billing-to-subscription`) wired first, plus a
deterministic fake payment/notification provider. See
[`docs/architecture/testing-strategy.md`](../../docs/architecture/testing-strategy.md)
for the plan and current status.

Not to be confused with the separate `kind`-based Kubernetes platform
smoke tests (infrastructure questions: does the Ingress route, does a
rolling update stay available — not business questions).
