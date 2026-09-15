<?php

namespace App\Presentation\Price\Adapters\Inbound\Http\Controllers;

use App\Application\Price\Commands\CreatePrice\CreatePriceCommand;
use App\Application\Price\Ports\Inbound\IPriceServicePort;
use App\Application\Price\Queries\GetPrice\GetPriceQuery;
use App\Presentation\Price\Adapters\Inbound\Http\Requests\CreatePriceRequest;
use App\Presentation\Price\Adapters\Inbound\Http\Resources\PriceResource;
use App\Shared\Presentation\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

final class PriceController extends Controller
{
    public function __construct(private readonly IPriceServicePort $priceService) {}

    public function store(CreatePriceRequest $request, string $product): JsonResponse
    {
        $price = $this->priceService->createPrice(new CreatePriceCommand(
            $product,
            $request->validated('amount_minor_units'),
            $request->validated('currency'),
            $request->validated('type'),
            $request->validated('billing_interval'),
            $request->validated('billing_interval_count'),
        ));

        return PriceResource::make($price)->response()->setStatusCode(201);
    }

    public function show(string $price): JsonResponse
    {
        $found = $this->priceService->getPrice(new GetPriceQuery($price));

        return PriceResource::make($found)->response();
    }
}
