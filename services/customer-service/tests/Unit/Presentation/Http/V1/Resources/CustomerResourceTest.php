<?php

use App\Domain\Customer\Customer;
use App\Domain\Customer\ValueObjects\CustomerId;
use App\Domain\Customer\ValueObjects\CustomerName;
use App\Domain\Customer\ValueObjects\Email;
use App\Presentation\Http\V1\Resources\CustomerResource;
use Illuminate\Http\Request;

test('toArray exposes the customer id, email and name', function () {
    $id = CustomerId::generate();
    $customer = Customer::create($id, aMerchantId(), Email::fromString('jane@example.com'), CustomerName::fromString('Jane Doe'));

    $array = (new CustomerResource($customer))->toArray(new Request);

    expect($array)->toBe([
        'id' => $id->toString(),
        'merchant_id' => aMerchantId()->toString(),
        'email' => 'jane@example.com',
        'name' => 'Jane Doe',
    ]);
});

test('constructing with a non-Customer value fails with a TypeError', function () {
    new CustomerResource('not-a-customer');
})->throws(TypeError::class);

test('toArray exposes a key for every Customer constructor parameter', function () {
    $customer = Customer::create(CustomerId::generate(), aMerchantId(), Email::fromString('jane@example.com'), CustomerName::fromString('Jane Doe'));

    $array = (new CustomerResource($customer))->toArray(new Request);

    $constructorParams = array_map(
        fn (ReflectionParameter $parameter) => $parameter->getName(),
        (new ReflectionClass(Customer::class))->getConstructor()->getParameters(),
    );

    $constructorParams = array_map(fn (string $name) => $name === 'merchantId' ? 'merchant_id' : $name, $constructorParams);

    expect(array_keys($array))->toEqualCanonicalizing($constructorParams);
});
