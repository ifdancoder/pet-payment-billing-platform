<?php

namespace App\Domain\Price;

use App\Domain\Price\Events\PriceCreated;
use App\Domain\Price\ValueObjects\BillingInterval;
use App\Domain\Price\ValueObjects\Money;
use App\Domain\Price\ValueObjects\PriceId;
use App\Domain\Product\ValueObjects\ProductId;

final class Price
{
    /** @var array<object> */
    private array $recordedEvents = [];

    private function __construct(
        private readonly PriceId $id,
        private readonly ProductId $productId,
        private readonly Money $money,
        private readonly BillingInterval $billingInterval,
    ) {}

    public static function create(
        PriceId $id,
        ProductId $productId,
        Money $money,
        BillingInterval $billingInterval,
    ): self {
        $price = new self($id, $productId, $money, $billingInterval);
        $price->recordEvent(new PriceCreated($id, $productId, $money, $billingInterval));

        return $price;
    }

    /**
     * Rebuilds a Price from already-persisted data. Unlike create(), this
     * does not record a PriceCreated event.
     */
    public static function reconstitute(
        PriceId $id,
        ProductId $productId,
        Money $money,
        BillingInterval $billingInterval,
    ): self {
        return new self($id, $productId, $money, $billingInterval);
    }

    public function id(): PriceId
    {
        return $this->id;
    }

    public function productId(): ProductId
    {
        return $this->productId;
    }

    public function money(): Money
    {
        return $this->money;
    }

    public function billingInterval(): BillingInterval
    {
        return $this->billingInterval;
    }

    /**
     * @return array<object>
     */
    public function pullRecordedEvents(): array
    {
        $events = $this->recordedEvents;
        $this->recordedEvents = [];

        return $events;
    }

    private function recordEvent(object $event): void
    {
        $this->recordedEvents[] = $event;
    }
}
