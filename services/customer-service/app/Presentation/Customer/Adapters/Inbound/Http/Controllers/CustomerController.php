<?php

namespace App\Presentation\Customer\Adapters\Inbound\Http\Controllers;

use App\Application\Customer\Commands\CreateCustomer\CreateCustomerCommand;
use App\Application\Customer\Ports\Inbound\ICustomerServicePort;
use App\Presentation\Customer\Adapters\Inbound\Http\Requests\CreateCustomerRequest;
use App\Shared\Presentation\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

final class CustomerController extends Controller
{
    public function __construct(private readonly ICustomerServicePort $customerService) {}

    public function store(CreateCustomerRequest $request): JsonResponse
    {
        $id = $this->customerService->createCustomer(new CreateCustomerCommand(
            $request->validated('email'),
            $request->validated('name'),
        ));

        return response()->json(['id' => $id->toString()], 201);
    }
}
