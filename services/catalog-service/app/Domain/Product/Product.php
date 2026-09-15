<?php

namespace App\Domain\Product;

use App\Domain\Product\Events\ProductCreated;
use App\Domain\Product\ValueObjects\ProductId;
use App\Domain\Product\ValueObjects\ProductName;

final class Product
{
    /** @var array<object> */
    private array $recordedEvents = [];

    private function __construct(
        private readonly ProductId $id,
        private ProductName $name,
    ) {}

    public static function create(ProductId $id, ProductName $name): self
    {
        $product = new self($id, $name);
        $product->recordEvent(new ProductCreated($id, $name));

        return $product;
    }

    /**
     * Rebuilds a Product from already-persisted data. Unlike create(), this
     * does not record a ProductCreated event.
     */
    public static function reconstitute(ProductId $id, ProductName $name): self
    {
        return new self($id, $name);
    }

    public function id(): ProductId
    {
        return $this->id;
    }

    public function name(): ProductName
    {
        return $this->name;
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
