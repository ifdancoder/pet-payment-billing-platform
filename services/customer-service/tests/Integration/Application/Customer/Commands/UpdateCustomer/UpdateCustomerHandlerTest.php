<?php

use App\Application\Customer\Commands\UpdateCustomer\UpdateCustomerCommand;
use App\Application\Customer\Commands\UpdateCustomer\UpdateCustomerHandler;
use App\Domain\Customer\Customer;
use App\Domain\Customer\Exceptions\CustomerNotFound;
use App\Domain\Customer\ValueObjects\CustomerId;
use App\Domain\Customer\ValueObjects\CustomerName;
use App\Domain\Customer\ValueObjects\Email;
use App\Infrastructure\Customer\Adapters\Persistence\Mappers\CustomerMapper;
use App\Infrastructure\Customer\Adapters\Persistence\Repositories\EloquentCustomerRepository;

test('handle updates the email and name of an existing customer', function () {
    $repository = new EloquentCustomerRepository(new CustomerMapper);
    $id = CustomerId::generate();
    $repository->save(Customer::create($id, aMerchantId(), Email::fromString('jane@example.com'), CustomerName::fromString('Jane Doe')));
    $handler = new UpdateCustomerHandler($repository);

    $updated = $handler->handle(new UpdateCustomerCommand(aMerchantId()->toString(), $id->toString(), 'jane.doe@example.com', 'Jane Smith'));

    expect($updated->email()->toString())->toBe('jane.doe@example.com')
        ->and($updated->name()->toString())->toBe('Jane Smith');
    $persisted = $repository->get($id, aMerchantId());
    expect($persisted->email()->toString())->toBe('jane.doe@example.com')
        ->and($persisted->name()->toString())->toBe('Jane Smith');
});

test('handle throws CustomerNotFound when no customer matches', function () {
    $handler = new UpdateCustomerHandler(new EloquentCustomerRepository(new CustomerMapper));

    $handler->handle(new UpdateCustomerCommand(aMerchantId()->toString(), CustomerId::generate()->toString(), 'jane@example.com', 'Jane Doe'));
})->throws(CustomerNotFound::class);
