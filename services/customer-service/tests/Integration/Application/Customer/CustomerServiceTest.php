<?php

use App\Application\Customer\Commands\CreateCustomer\CreateCustomerCommand;
use App\Application\Customer\Commands\UpdateCustomer\UpdateCustomerCommand;
use App\Application\Customer\CustomerService;
use App\Application\Customer\Queries\GetCustomer\GetCustomerQuery;
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
