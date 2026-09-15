<?php

namespace App\Application\Product\Commands\CreateProduct;

final class CreateProductCommand
{
    public function __construct(public readonly string $name) {}
}
