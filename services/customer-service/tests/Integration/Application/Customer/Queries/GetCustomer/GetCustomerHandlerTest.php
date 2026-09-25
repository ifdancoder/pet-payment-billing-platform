<?php

use App\Application\Customer\Queries\GetCustomer\GetCustomerHandler;
use App\Application\Customer\Queries\GetCustomer\GetCustomerQuery;
use App\Domain\Customer\Customer;
use App\Domain\Customer\Exceptions\CustomerNotFound;
use App\Domain\Customer\ValueObjects\CustomerId;
use App\Domain\Customer\ValueObjects\CustomerName;
use App\Domain\Customer\ValueObjects\Email;
use App\Infrastructure\Customer\Adapters\Persistence\Mappers\CustomerMapper;
use App\Infrastructure\Customer\Adapters\Persistence\Repositories\EloquentCustomerRepository;

test('handle returns the customer matching the given id', function () {
    $repository = new EloquentCustomerRepository(new CustomerMapper);
    $id = CustomerId::generate();
    $repository->save(Customer::create($id, aMerchantId(), Email::fromString('jane@example.com'), CustomerName::fromString('Jane Doe')));
    $handler = new GetCustomerHandler($repository);

    $customer = $handler->handle(new GetCustomerQuery(aMerchantId()->toString(), $id->toString()));

    expect($customer->email()->toString())->toBe('jane@example.com')
        ->and($customer->name()->toString())->toBe('Jane Doe');
});

test('handle throws CustomerNotFound when no customer matches', function () {
    $handler = new GetCustomerHandler(new EloquentCustomerRepository(new CustomerMapper));

    $handler->handle(new GetCustomerQuery(aMerchantId()->toString(), CustomerId::generate()->toString()));
})->throws(CustomerNotFound::class);
