<?php

use App\Application\Customer\Commands\DeleteCustomer\DeleteCustomerCommand;
use App\Application\Customer\Commands\DeleteCustomer\DeleteCustomerHandler;
use App\Domain\Customer\Customer;
use App\Domain\Customer\Exceptions\CustomerNotFound;
use App\Domain\Customer\ValueObjects\CustomerId;
use App\Domain\Customer\ValueObjects\CustomerName;
use App\Domain\Customer\ValueObjects\Email;
use App\Infrastructure\Customer\Adapters\Persistence\Mappers\CustomerMapper;
use App\Infrastructure\Customer\Adapters\Persistence\Repositories\EloquentCustomerRepository;

test('handle deletes an existing customer', function () {
    $repository = new EloquentCustomerRepository(new CustomerMapper);
    $id = CustomerId::generate();
    $repository->save(Customer::create($id, aMerchantId(), Email::fromString('jane@example.com'), CustomerName::fromString('Jane Doe')));
    $handler = new DeleteCustomerHandler($repository);

    $handler->handle(new DeleteCustomerCommand(aMerchantId()->toString(), $id->toString()));

    expect(fn () => $repository->get($id, aMerchantId()))->toThrow(CustomerNotFound::class);
});

test('handle throws CustomerNotFound when no customer matches', function () {
    $handler = new DeleteCustomerHandler(new EloquentCustomerRepository(new CustomerMapper));

    $handler->handle(new DeleteCustomerCommand(aMerchantId()->toString(), CustomerId::generate()->toString()));
})->throws(CustomerNotFound::class);
