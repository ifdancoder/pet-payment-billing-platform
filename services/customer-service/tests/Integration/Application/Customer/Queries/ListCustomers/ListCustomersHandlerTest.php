<?php

use App\Application\Customer\Queries\ListCustomers\ListCustomersHandler;
use App\Application\Customer\Queries\ListCustomers\ListCustomersQuery;
use App\Domain\Customer\Customer;
use App\Domain\Customer\ValueObjects\CustomerId;
use App\Domain\Customer\ValueObjects\CustomerName;
use App\Domain\Customer\ValueObjects\Email;
use App\Infrastructure\Customer\Adapters\Persistence\Mappers\CustomerMapper;
use App\Infrastructure\Customer\Adapters\Persistence\Repositories\EloquentCustomerRepository;

test('handle returns every persisted customer', function () {
    $repository = new EloquentCustomerRepository(new CustomerMapper);
    $repository->save(Customer::create(CustomerId::generate(), aMerchantId(), Email::fromString('jane@example.com'), CustomerName::fromString('Jane Doe')));
    $repository->save(Customer::create(CustomerId::generate(), aMerchantId(), Email::fromString('john@example.com'), CustomerName::fromString('John Doe')));
    $handler = new ListCustomersHandler($repository);

    $customers = $handler->handle(new ListCustomersQuery(aMerchantId()->toString()));

    expect($customers)->toHaveCount(2);
});

test('handle returns an empty array when there are no customers', function () {
    $handler = new ListCustomersHandler(new EloquentCustomerRepository(new CustomerMapper));

    expect($handler->handle(new ListCustomersQuery(aMerchantId()->toString())))->toBe([]);
});
