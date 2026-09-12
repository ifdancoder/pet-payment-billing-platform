<?php

use App\Application\Customer\Commands\CreateCustomer\CreateCustomerCommand;
use App\Application\Customer\CustomerService;
use Illuminate\Support\Facades\Mail;

test('createCustomer delegates to CreateCustomerHandler and returns the created customer', function () {
    Mail::fake();
    $service = app(CustomerService::class);

    $customer = $service->createCustomer(new CreateCustomerCommand('jane@example.com', 'Jane Doe'));

    expect($customer->email()->toString())->toBe('jane@example.com');
});
