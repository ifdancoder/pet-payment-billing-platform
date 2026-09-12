<?php

use App\Domain\Customer\Customer;
use App\Domain\Customer\ValueObjects\CustomerId;
use App\Domain\Customer\ValueObjects\CustomerName;
use App\Domain\Customer\ValueObjects\Email;
use App\Infrastructure\Customer\Adapters\Persistence\Mappers\CustomerMapper;
use App\Infrastructure\Customer\Adapters\Persistence\Models\CustomerModel;
use App\Infrastructure\Customer\Adapters\Persistence\Repositories\EloquentCustomerRepository;

test('save persists a new customer', function () {
    $repository = new EloquentCustomerRepository(new CustomerMapper);
    $customer = Customer::create(CustomerId::generate(), Email::fromString('jane@example.com'), CustomerName::fromString('Jane Doe'));

    $repository->save($customer);

    expect(CustomerModel::query()->where('id', $customer->id()->toString())->exists())->toBeTrue();
});

test('save updates an already-persisted customer instead of duplicating it', function () {
    $repository = new EloquentCustomerRepository(new CustomerMapper);
    $id = CustomerId::generate();
    $repository->save(Customer::create($id, Email::fromString('old@example.com'), CustomerName::fromString('Old Name')));

    $repository->save(Customer::reconstitute($id, Email::fromString('new@example.com'), CustomerName::fromString('New Name')));

    expect(CustomerModel::query()->where('id', $id->toString())->count())->toBe(1)
        ->and(CustomerModel::query()->find($id->toString())->email)->toBe('new@example.com');
});

test('findById returns the matching customer', function () {
    $repository = new EloquentCustomerRepository(new CustomerMapper);
    $id = CustomerId::generate();
    $repository->save(Customer::create($id, Email::fromString('jane@example.com'), CustomerName::fromString('Jane Doe')));

    $found = $repository->findById($id);

    expect($found)->not->toBeNull()
        ->and($found->id()->equals($id))->toBeTrue()
        ->and($found->email()->toString())->toBe('jane@example.com')
        ->and($found->name()->toString())->toBe('Jane Doe');
});

test('findById returns null when no customer matches', function () {
    $repository = new EloquentCustomerRepository(new CustomerMapper);

    expect($repository->findById(CustomerId::generate()))->toBeNull();
});
