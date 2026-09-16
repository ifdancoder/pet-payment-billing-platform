<?php

namespace App\Domain\Product;

use App\Domain\Product\Events\ProductArchived;
use App\Domain\Product\Events\ProductCreated;
use App\Domain\Product\Exceptions\ArchivedProductCannotBeModified;
use App\Domain\Product\Exceptions\ProductAlreadyArchived;
use App\Domain\Product\ValueObjects\ProductId;
use App\Domain\Product\ValueObjects\ProductName;
use App\Domain\Product\ValueObjects\ProductStatus;
use App\Shared\Domain\ValueObjects\MerchantId;

final class Product
{
    /** @var array<object> */
    private array $recordedEvents = [];

    private function __construct(
        private readonly ProductId $id,
        private readonly MerchantId $merchantId,
        private ProductName $name,
        private ?string $description,
        private ProductStatus $status,
    ) {}

    public static function create(ProductId $id, MerchantId $merchantId, ProductName $name, ?string $description = null): self
    {
        $product = new self($id, $merchantId, $name, $description, ProductStatus::Active);
        $product->recordEvent(new ProductCreated($id, $name));

        return $product;
    }

    public static function reconstitute(
        ProductId $id,
        MerchantId $merchantId,
        ProductName $name,
        ?string $description,
        ProductStatus $status,
    ): self {
        return new self($id, $merchantId, $name, $description, $status);
    }

    public function id(): ProductId
    {
        return $this->id;
    }

    public function merchantId(): MerchantId
    {
        return $this->merchantId;
    }

    public function name(): ProductName
    {
        return $this->name;
    }

    public function description(): ?string
    {
        return $this->description;
    }

    public function status(): ProductStatus
    {
        return $this->status;
    }

    public function rename(ProductName $name): void
    {
        if ($this->status === ProductStatus::Archived) {
            throw ArchivedProductCannotBeModified::withId($this->id);
        }

        $this->name = $name;
    }

    public function archive(): void
    {
        if ($this->status === ProductStatus::Archived) {
            throw ProductAlreadyArchived::withId($this->id);
        }

        $this->status = ProductStatus::Archived;
        $this->recordEvent(new ProductArchived($this->id));
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
