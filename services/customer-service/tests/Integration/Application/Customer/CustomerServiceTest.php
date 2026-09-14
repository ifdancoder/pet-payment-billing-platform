<?php

use App\Application\Customer\Commands\CreateCustomer\CreateCustomerCommand;
use App\Application\Customer\Commands\DeleteCustomer\DeleteCustomerCommand;
use App\Application\Customer\Commands\UpdateCustomer\UpdateCustomerCommand;
use App\Application\Customer\CustomerService;
use App\Application\Customer\Ports\Outbound\ICustomerRepositoryPort;
use App\Application\Customer\Queries\GetCustomer\GetCustomerQuery;
use App\Application\Customer\Queries\ListCustomers\ListCustomersQuery;
use App\Domain\Customer\Exceptions\CustomerNotFound;
use Illuminate\Support\Facades\Mail;

test('createCustomer delegates to CreateCustomerHandler and returns the created customer', function () {
    Mail::fake();
    $service = app(CustomerService::class);

    $customer = $service->createCustomer(new CreateCustomerCommand('jane@example.com', 'Jane Doe'));

    expect($customer->email()->toString())->toBe('jane@example.com');
});

test('getCustomer delegates to GetCustomerHandler and returns the matching customer', function () {
    Mail::fake();
    $service = app(CustomerService::class);
    $created = $service->createCustomer(new CreateCustomerCommand('jane@example.com', 'Jane Doe'));

    $customer = $service->getCustomer(new GetCustomerQuery($created->id()->toString()));

    expect($customer->id()->equals($created->id()))->toBeTrue();
});

test('updateCustomer delegates to UpdateCustomerHandler and returns the updated customer', function () {
    Mail::fake();
    $service = app(CustomerService::class);
    $created = $service->createCustomer(new CreateCustomerCommand('jane@example.com', 'Jane Doe'));

    $updated = $service->updateCustomer(new UpdateCustomerCommand($created->id()->toString(), 'jane.doe@example.com', 'Jane Smith'));

    expect($updated->email()->toString())->toBe('jane.doe@example.com')
        ->and($updated->name()->toString())->toBe('Jane Smith');
});

test('deleteCustomer delegates to DeleteCustomerHandler and removes the customer', function () {
    Mail::fake();
    $service = app(CustomerService::class);
    $created = $service->createCustomer(new CreateCustomerCommand('jane@example.com', 'Jane Doe'));

    $service->deleteCustomer(new DeleteCustomerCommand($created->id()->toString()));

    expect(fn () => app(ICustomerRepositoryPort::class)->get($created->id()))->toThrow(CustomerNotFound::class);
});

test('listCustomers delegates to ListCustomersHandler and returns every customer', function () {
    Mail::fake();
    $service = app(CustomerService::class);
    $service->createCustomer(new CreateCustomerCommand('jane@example.com', 'Jane Doe'));
    $service->createCustomer(new CreateCustomerCommand('john@example.com', 'John Doe'));

    $customers = $service->listCustomers(new ListCustomersQuery);

    expect($customers)->toHaveCount(2);
});
