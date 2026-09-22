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
- [`failed-payment/`](failed-payment/) — done, see its own README. The
  first failure-path scenario: same seven services, but the Price's
  amount is `FakePaymentGateway`'s reserved decline-trigger value, so
  the charge is guaranteed to decline. Proves the Invoice stays Open,
  the Payment ends up Failed with a real provider failure code, and the
  Subscription — never having reached Active — stays Pending rather
  than PastDue.

Not built yet: `overdue-subscription` — blocked on a real production
feature this platform doesn't have, not on test infrastructure: there's
no scheduled job that creates a *second* billing cycle's Invoice for an
Active subscription, so there's no way to reach a "previously Active,
now overdue" state to test against. The PastDue transition it would
exercise is already fully covered anyway, by
[`tests/integration/billing-to-subscription/`](../integration/billing-to-subscription/)'s
own second test. See "Building the next slice" in the testing strategy
doc.

Not to be confused with the separate `kind`-based Kubernetes platform
smoke tests (infrastructure questions: does the Ingress route, does a
rolling update stay available — not business questions).
