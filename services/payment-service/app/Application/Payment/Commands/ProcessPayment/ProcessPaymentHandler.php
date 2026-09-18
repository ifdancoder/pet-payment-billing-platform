<?php

namespace App\Application\Payment\Commands\ProcessPayment;

use App\Application\Payment\DataTransferObjects\ChargeRequest;
use App\Application\Payment\DataTransferObjects\ChargeStatus;
use App\Application\Payment\IntegrationEvents\PaymentFailedIntegrationEvent;
use App\Application\Payment\IntegrationEvents\PaymentSucceededIntegrationEvent;
use App\Application\Payment\Ports\Outbound\IPaymentGatewayPort;
use App\Application\Payment\Ports\Outbound\IPaymentRepositoryPort;
use App\Domain\Payment\Events\PaymentFailed;
use App\Domain\Payment\Events\PaymentSucceeded;
use App\Domain\Payment\Payment;
use App\Domain\Payment\ValueObjects\PaymentAttemptId;
use App\Domain\Payment\ValueObjects\PaymentId;
use App\Domain\Payment\ValueObjects\ProviderReference;
use App\Shared\Application\Ports\Outbound\IOutboxPort;
use App\Shared\Application\Ports\Outbound\ITransactionManagerPort;
use App\Shared\Domain\ValueObjects\MerchantId;
use DateTimeImmutable;

/**
 * The one handler in this service that talks to the network. The DB
 * transaction never spans that call: a short transaction starts the
 * attempt and commits, then the gateway is called with no transaction
 * open at all, then a second short transaction applies whatever the
 * gateway said.
 */
final class ProcessPaymentHandler
{
    /**
     * Which provider is charging — hardcoded until a second one (Stripe)
     * exists to actually choose between.
     */
    private const string PROVIDER = 'fake';

    public function __construct(
        private readonly IPaymentRepositoryPort $repository,
        private readonly IPaymentGatewayPort $gateway,
        private readonly IOutboxPort $outbox,
        private readonly ITransactionManagerPort $transaction,
    ) {}

    public function handle(ProcessPaymentCommand $command): Payment
    {
        $paymentId = PaymentId::fromString($command->paymentId);
        $merchantId = MerchantId::fromString($command->merchantId);
        $attemptId = PaymentAttemptId::generate();

        $payment = $this->repository->get($paymentId, $merchantId);

        $this->transaction->run(function () use ($payment, $attemptId): void {
            $payment->startAttempt($attemptId, self::PROVIDER, new DateTimeImmutable);
            $this->repository->save($payment);
        });

        $result = $this->gateway->charge(new ChargeRequest($attemptId, $payment->money()));

        $this->transaction->run(function () use ($payment, $attemptId, $result): void {
            match ($result->status) {
                ChargeStatus::Succeeded => $payment->succeed(
                    $attemptId,
                    ProviderReference::of($result->providerReference),
                    new DateTimeImmutable,
                ),
                ChargeStatus::Failed => $payment->fail($attemptId, $result->failureCode, null, new DateTimeImmutable),
                // Some payment methods don't return a synchronous final
                // result — the attempt stays Pending until a provider
                // webhook confirms it. Webhook handling isn't built yet.
                ChargeStatus::Pending => null,
            };

            $this->repository->save($payment);
            $this->recordIntegrationEvents($payment);
        });

        return $payment;
    }

    private function recordIntegrationEvents(Payment $payment): void
    {
        foreach ($payment->pullRecordedEvents() as $event) {
            if ($event instanceof PaymentSucceeded) {
                $this->outbox->add(PaymentSucceededIntegrationEvent::fromDomainEvent($event));
            }

            if ($event instanceof PaymentFailed) {
                $this->outbox->add(PaymentFailedIntegrationEvent::fromDomainEvent($event));
            }
        }
    }
}
