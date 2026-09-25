<?php

namespace App\Presentation\Http\V1\Controllers;

use App\Application\Customer\Commands\CreateCustomer\CreateCustomerCommand;
use App\Application\Customer\Commands\DeleteCustomer\DeleteCustomerCommand;
use App\Application\Customer\Commands\UpdateCustomer\UpdateCustomerCommand;
use App\Application\Customer\Ports\Inbound\ICustomerServicePort;
use App\Application\Customer\Queries\GetCustomer\GetCustomerQuery;
use App\Application\Customer\Queries\ListCustomers\ListCustomersQuery;
use App\Presentation\Http\V1\Requests\CreateCustomerRequest;
use App\Presentation\Http\V1\Requests\UpdateCustomerRequest;
use App\Presentation\Http\V1\Resources\CustomerResource;
use App\Presentation\Http\V1\Resources\CustomerResourceCollection;
use App\Shared\Presentation\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

final class CustomerController extends Controller
{
    public function __construct(private readonly ICustomerServicePort $customerService) {}

    public function index(string $merchant): JsonResponse
    {
        $customers = $this->customerService->listCustomers(new ListCustomersQuery($merchant));

        return (new CustomerResourceCollection($customers))->response();
    }

    public function store(string $merchant, CreateCustomerRequest $request): JsonResponse
    {
        $customer = $this->customerService->createCustomer(new CreateCustomerCommand(
            $merchant,
            $request->validated('email'),
            $request->validated('name'),
        ));

        return CustomerResource::make($customer)->response()->setStatusCode(201);
    }

    public function show(string $merchant, string $customer): JsonResponse
    {
        $customer = $this->customerService->getCustomer(new GetCustomerQuery($merchant, $customer));

        return CustomerResource::make($customer)->response();
    }

    public function update(string $merchant, string $customer, UpdateCustomerRequest $request): JsonResponse
    {
        $customer = $this->customerService->updateCustomer(new UpdateCustomerCommand(
            $merchant,
            $customer,
            $request->validated('email'),
            $request->validated('name'),
        ));

        return CustomerResource::make($customer)->response();
    }

    public function destroy(string $merchant, string $customer): Response
    {
        $this->customerService->deleteCustomer(new DeleteCustomerCommand($merchant, $customer));

        return response()->noContent();
    }
}
