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

    public function index(): JsonResponse
    {
        $customers = $this->customerService->listCustomers(new ListCustomersQuery);

        return (new CustomerResourceCollection($customers))->response();
    }

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

    public function update(string $id, UpdateCustomerRequest $request): JsonResponse
    {
        $customer = $this->customerService->updateCustomer(new UpdateCustomerCommand(
            $id,
            $request->validated('email'),
            $request->validated('name'),
        ));

        return CustomerResource::make($customer)->response();
    }

    public function destroy(string $id): Response
    {
        $this->customerService->deleteCustomer(new DeleteCustomerCommand($id));

        return response()->noContent();
    }
}
