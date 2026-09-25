# End-to-end tests

*[Русская версия](README.ru.md)*

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

- [`overdue-subscription/`](overdue-subscription/) — done, together with
  the recurring-billing production feature it required. The initial
  Invoice is genuinely paid and the Subscription becomes Active; the
  scheduler then creates the next cycle's Invoice, its renewal-only fake
  charge declines, and the real Payment → Billing → Subscription chain
  moves it to PastDue. See its own README.

Not to be confused with the separate `kind`-based Kubernetes platform
smoke tests (infrastructure questions: does the Ingress route, does a
rolling update stay available — not business questions).
