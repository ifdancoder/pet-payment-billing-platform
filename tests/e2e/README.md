# End-to-end tests

Full business flows across every service (Identity through
Notification), with fake payment/email providers instead of real ones.
See
[`docs/architecture/testing-strategy.md`](../../docs/architecture/testing-strategy.md)
for the full plan and current status.

- [`successful-subscription/`](successful-subscription/) — done, see
  its own README. The happy path: Merchant → Customer → Product/Price →
  Subscription → Invoice → Payment → Subscription Active →
  Notification.

Not built yet: failure-path scenarios (`failed-payment`,
`overdue-subscription`) — both need deterministic *control* over the
fake payment provider's outcome from the test side, which doesn't
exist yet (the fake provider itself does, and always succeeds — see
the testing strategy doc's "Fake providers" section).

Not to be confused with the separate `kind`-based Kubernetes platform
smoke tests (infrastructure questions: does the Ingress route, does a
rolling update stay available — not business questions).
