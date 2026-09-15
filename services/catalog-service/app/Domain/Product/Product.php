<?php

namespace App\Domain\Product;

use App\Domain\Product\Events\ProductArchived;
use App\Domain\Product\Events\ProductCreated;
use App\Domain\Product\Exceptions\ArchivedProductCannotBeModified;
use App\Domain\Product\Exceptions\ProductAlreadyArchived;
use App\Domain\Product\ValueObjects\ProductId;
use App\Domain\Product\ValueObjects\ProductName;
use App\Domain\Product\ValueObjects\ProductStatus;

final class Product
{
    /** @var array<object> */
    private array $recordedEvents = [];

    private function __construct(
        private readonly ProductId $id,
        private ProductName $name,
        private ProductStatus $status,
    ) {}

    public static function create(ProductId $id, ProductName $name): self
    {
        $product = new self($id, $name, ProductStatus::Active);
        $product->recordEvent(new ProductCreated($id, $name));

        return $product;
    }

    /**
     * Rebuilds a Product from already-persisted data. Unlike create(), this
     * does not record a ProductCreated event.
     */
    public static function reconstitute(ProductId $id, ProductName $name, ProductStatus $status): self
    {
        return new self($id, $name, $status);
    }

    public function id(): ProductId
    {
        return $this->id;
    }

    public function name(): ProductName
    {
        return $this->name;
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
