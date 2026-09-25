<?php

use App\Domain\Customer\Customer;
use App\Domain\Customer\ValueObjects\CustomerId;
use App\Domain\Customer\ValueObjects\CustomerName;
use App\Domain\Customer\ValueObjects\Email;
use App\Infrastructure\Customer\Adapters\Persistence\Mappers\CustomerMapper;
use App\Infrastructure\Customer\Adapters\Persistence\Models\CustomerModel;

test('toDomain builds a Customer matching the model attributes', function () {
    $model = new CustomerModel([
        'id' => '9f8e7d6c-5b4a-4321-9876-abcdef012345',
        'merchant_id' => aMerchantId()->toString(),
        'email' => 'jane@example.com',
        'name' => 'Jane Doe',
    ]);

    $customer = (new CustomerMapper)->toDomain($model);

    expect($customer->id()->toString())->toBe('9f8e7d6c-5b4a-4321-9876-abcdef012345')
        ->and($customer->email()->toString())->toBe('jane@example.com')
        ->and($customer->name()->toString())->toBe('Jane Doe');
});

test('toDomain does not record a CustomerCreated event', function () {
    $model = new CustomerModel([
        'id' => '9f8e7d6c-5b4a-4321-9876-abcdef012345',
        'merchant_id' => aMerchantId()->toString(),
        'email' => 'jane@example.com',
        'name' => 'Jane Doe',
    ]);

    $customer = (new CustomerMapper)->toDomain($model);

    expect($customer->pullRecordedEvents())->toBe([]);
});

test('toModel fills a new model from a Customer', function () {
    $customer = Customer::create(
        CustomerId::fromString('9f8e7d6c-5b4a-4321-9876-abcdef012345'),
        aMerchantId(),
        Email::fromString('jane@example.com'),
        CustomerName::fromString('Jane Doe'),
    );

    $model = (new CustomerMapper)->toModel($customer);

    expect($model->id)->toBe('9f8e7d6c-5b4a-4321-9876-abcdef012345')
        ->and($model->email)->toBe('jane@example.com')
        ->and($model->name)->toBe('Jane Doe');
});

test('toModel fills an existing model instance in place instead of creating a new one', function () {
    $existing = new CustomerModel([
        'id' => '9f8e7d6c-5b4a-4321-9876-abcdef012345',
        'merchant_id' => aMerchantId()->toString(),
        'email' => 'old@example.com',
        'name' => 'Old Name',
    ]);
    $customer = Customer::create(
        CustomerId::fromString('9f8e7d6c-5b4a-4321-9876-abcdef012345'),
        aMerchantId(),
        Email::fromString('new@example.com'),
        CustomerName::fromString('New Name'),
    );

    $model = (new CustomerMapper)->toModel($customer, $existing);

    expect($model)->toBe($existing)
        ->and($model->email)->toBe('new@example.com')
        ->and($model->name)->toBe('New Name');
});

test('toModel sets a model attribute for every Customer constructor parameter', function () {
    $customer = Customer::create(
        CustomerId::fromString('9f8e7d6c-5b4a-4321-9876-abcdef012345'),
        aMerchantId(),
        Email::fromString('jane@example.com'),
        CustomerName::fromString('Jane Doe'),
    );

    $model = (new CustomerMapper)->toModel($customer);

    $constructorParams = array_map(
        fn (ReflectionParameter $parameter) => $parameter->getName(),
        (new ReflectionClass(Customer::class))->getConstructor()->getParameters(),
    );
    foreach ($constructorParams as $param) {
        $column = $param === 'merchantId' ? 'merchant_id' : $param;
        expect((string) $model->{$column})->toBe($customer->{$param}()->toString());
    }
});

test('toDomain builds a Customer using every constructor parameter from the model', function () {
    $model = new CustomerModel([
        'id' => '9f8e7d6c-5b4a-4321-9876-abcdef012345',
        'merchant_id' => aMerchantId()->toString(),
        'email' => 'jane@example.com',
        'name' => 'Jane Doe',
    ]);

    $customer = (new CustomerMapper)->toDomain($model);

    $constructorParams = array_map(
        fn (ReflectionParameter $parameter) => $parameter->getName(),
        (new ReflectionClass(Customer::class))->getConstructor()->getParameters(),
    );
    foreach ($constructorParams as $param) {
        $column = $param === 'merchantId' ? 'merchant_id' : $param;
        expect($customer->{$param}()->toString())->toBe((string) $model->{$column});
    }
});
