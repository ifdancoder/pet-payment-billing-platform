<?php

namespace App\Presentation\Customer\Adapters\Inbound\Http\Controllers;

use App\Application\Customer\Commands\CreateCustomer\CreateCustomerCommand;
use App\Application\Customer\Ports\Inbound\ICustomerServicePort;
use App\Application\Customer\Queries\GetCustomer\GetCustomerQuery;
use App\Presentation\Customer\Adapters\Inbound\Http\Requests\CreateCustomerRequest;
use App\Presentation\Customer\Adapters\Inbound\Http\Resources\CustomerResource;
use App\Shared\Presentation\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

final class CustomerController extends Controller
{
    public function __construct(private readonly ICustomerServicePort $customerService) {}

    public function store(CreateCustomerRequest $request): JsonResponse
    {
        $customer = $this->customerService->createCustomer(new CreateCustomerCommand(
            $request->validated('email'),
            $request->validated('name'),
        ));

        return CustomerResource::make($customer)->response()->setStatusCode(201);
    }

    public function show(string $id): JsonResponse
    {
        $customer = $this->customerService->getCustomer(new GetCustomerQuery($id));

        return CustomerResource::make($customer)->response();
    }
}
