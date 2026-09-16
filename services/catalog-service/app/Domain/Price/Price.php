<?php

namespace App\Domain\Price;

use App\Domain\Price\Events\PriceActivated;
use App\Domain\Price\Events\PriceCreated;
use App\Domain\Price\Events\PriceDeactivated;
use App\Domain\Price\Exceptions\InvalidPrice;
use App\Domain\Price\Exceptions\PriceAlreadyActive;
use App\Domain\Price\Exceptions\PriceAlreadyInactive;
use App\Domain\Price\ValueObjects\BillingPeriod;
use App\Domain\Price\ValueObjects\Money;
use App\Domain\Price\ValueObjects\PriceId;
use App\Domain\Price\ValueObjects\PriceStatus;
use App\Domain\Price\ValueObjects\PriceType;
use App\Domain\Product\ValueObjects\ProductId;
use App\Shared\Domain\ValueObjects\MerchantId;

final class Price
{
    /** @var array<object> */
    private array $recordedEvents = [];

    private function __construct(
        private readonly PriceId $id,
        private readonly MerchantId $merchantId,
        private readonly ProductId $productId,
        private readonly Money $money,
        private readonly PriceType $type,
        private readonly ?BillingPeriod $billingPeriod,
        private PriceStatus $status,
    ) {}

    public static function create(
        PriceId $id,
        MerchantId $merchantId,
        ProductId $productId,
        Money $money,
        PriceType $type,
        ?BillingPeriod $billingPeriod = null,
    ): self {
        self::assertBillingPeriodMatchesType($type, $billingPeriod);

        $price = new self($id, $merchantId, $productId, $money, $type, $billingPeriod, PriceStatus::Active);
        $price->recordEvent(new PriceCreated($id, $productId, $money, $type, $billingPeriod));

        return $price;
    }

    public static function reconstitute(
        PriceId $id,
        MerchantId $merchantId,
        ProductId $productId,
        Money $money,
        PriceType $type,
        ?BillingPeriod $billingPeriod,
        PriceStatus $status,
    ): self {
        return new self($id, $merchantId, $productId, $money, $type, $billingPeriod, $status);
    }

    public function id(): PriceId
    {
        return $this->id;
    }

    public function merchantId(): MerchantId
    {
        return $this->merchantId;
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

    public function status(): PriceStatus
    {
        return $this->status;
    }

    public function activate(): void
    {
        if ($this->status === PriceStatus::Active) {
            throw PriceAlreadyActive::withId($this->id);
        }

        $this->status = PriceStatus::Active;
        $this->recordEvent(new PriceActivated($this->id));
    }

    public function deactivate(): void
    {
        if ($this->status === PriceStatus::Inactive) {
            throw PriceAlreadyInactive::withId($this->id);
        }

        $this->status = PriceStatus::Inactive;
        $this->recordEvent(new PriceDeactivated($this->id));
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
