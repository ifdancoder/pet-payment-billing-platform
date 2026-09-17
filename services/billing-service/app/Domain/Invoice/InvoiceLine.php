<?php

namespace App\Domain\Invoice;

use App\Domain\Invoice\Exceptions\InvalidInvoiceLine;
use App\Domain\Invoice\ValueObjects\InvoiceLineId;
use App\Domain\Invoice\ValueObjects\Money;
use App\Domain\Invoice\ValueObjects\PriceId;
use App\Domain\Invoice\ValueObjects\ProductId;

final class InvoiceLine
{
    private function __construct(
        private readonly InvoiceLineId $id,
        private readonly ?ProductId $productId,
        private readonly ?PriceId $priceId,
        private readonly string $description,
        private readonly Money $unitAmount,
        private readonly int $quantity,
        private readonly Money $total,
    ) {}

    public static function create(
        InvoiceLineId $id,
        ?ProductId $productId,
        ?PriceId $priceId,
        string $description,
        Money $unitAmount,
        int $quantity,
    ): self {
        if ($quantity < 1) {
            throw InvalidInvoiceLine::quantityMustBeAtLeastOne($quantity);
        }

        return new self($id, $productId, $priceId, $description, $unitAmount, $quantity, $unitAmount->multiply($quantity));
    }

    public function id(): InvoiceLineId
    {
        return $this->id;
    }

    public function productId(): ?ProductId
    {
        return $this->productId;
    }

    public function priceId(): ?PriceId
    {
        return $this->priceId;
    }

    public function description(): string
    {
        return $this->description;
    }

    public function unitAmount(): Money
    {
        return $this->unitAmount;
    }

    public function quantity(): int
    {
        return $this->quantity;
    }

    public function total(): Money
    {
        return $this->total;
    }

    public function equals(self $other): bool
    {
        return $this->id->equals($other->id)
            && $this->description === $other->description
            && $this->unitAmount->equals($other->unitAmount)
            && $this->quantity === $other->quantity
            && $this->total->equals($other->total);
    }
}
