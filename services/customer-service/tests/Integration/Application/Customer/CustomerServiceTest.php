<?php

use App\Application\Customer\Commands\CreateCustomer\CreateCustomerCommand;
use App\Application\Customer\CustomerService;
use App\Application\Customer\Ports\Outbound\ICustomerRepositoryPort;
use Illuminate\Support\Facades\Mail;

test('createCustomer delegates to CreateCustomerHandler and returns the new customer id', function () {
    Mail::fake();
    $service = app(CustomerService::class);

    $id = $service->createCustomer(new CreateCustomerCommand('jane@example.com', 'Jane Doe'));

    expect(app(ICustomerRepositoryPort::class)->findById($id))->not->toBeNull();
});
