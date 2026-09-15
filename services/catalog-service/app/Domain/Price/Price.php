<?php

namespace App\Domain\Price;

use App\Domain\Price\Events\PriceCreated;
use App\Domain\Price\Exceptions\InvalidPrice;
use App\Domain\Price\ValueObjects\BillingPeriod;
use App\Domain\Price\ValueObjects\Money;
use App\Domain\Price\ValueObjects\PriceId;
use App\Domain\Price\ValueObjects\PriceType;
use App\Domain\Product\ValueObjects\ProductId;

final class Price
{
    /** @var array<object> */
    private array $recordedEvents = [];

    private function __construct(
        private readonly PriceId $id,
        private readonly ProductId $productId,
        private readonly Money $money,
        private readonly PriceType $type,
        private readonly ?BillingPeriod $billingPeriod,
    ) {}

    public static function create(
        PriceId $id,
        ProductId $productId,
        Money $money,
        PriceType $type,
        ?BillingPeriod $billingPeriod = null,
    ): self {
        self::assertBillingPeriodMatchesType($type, $billingPeriod);

        $price = new self($id, $productId, $money, $type, $billingPeriod);
        $price->recordEvent(new PriceCreated($id, $productId, $money, $type, $billingPeriod));

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
        PriceType $type,
        ?BillingPeriod $billingPeriod,
    ): self {
        return new self($id, $productId, $money, $type, $billingPeriod);
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

    public function type(): PriceType
    {
        return $this->type;
    }

    public function billingPeriod(): ?BillingPeriod
    {
        return $this->billingPeriod;
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

    private static function assertBillingPeriodMatchesType(PriceType $type, ?BillingPeriod $billingPeriod): void
    {
        if ($type === PriceType::Recurring && $billingPeriod === null) {
            throw InvalidPrice::billingPeriodRequiredForRecurring();
        }

        if ($type === PriceType::OneTime && $billingPeriod !== null) {
            throw InvalidPrice::billingPeriodNotAllowedForOneTime();
        }
    }

    private function recordEvent(object $event): void
    {
        $this->recordedEvents[] = $event;
    }
}
