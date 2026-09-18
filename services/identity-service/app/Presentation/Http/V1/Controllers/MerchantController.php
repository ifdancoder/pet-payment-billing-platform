<?php

namespace App\Presentation\Http\V1\Controllers;

use App\Application\Merchant\Commands\CreateMerchant\CreateMerchantCommand;
use App\Application\Merchant\Ports\Inbound\IMerchantServicePort;
use App\Presentation\Http\V1\Requests\CreateMerchantRequest;
use App\Presentation\Http\V1\Resources\MerchantResource;
use App\Shared\Presentation\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

final class MerchantController extends Controller
{
    public function __construct(private readonly IMerchantServicePort $merchantService) {}

    public function store(CreateMerchantRequest $request): JsonResponse
    {
        $merchant = $this->merchantService->createMerchant(new CreateMerchantCommand($request->validated('name')));

        return MerchantResource::make($merchant)->response()->setStatusCode(201);
    }
}
