<?php

use App\Domain\Customer\Customer;
use App\Domain\Customer\ValueObjects\CustomerId;
use App\Domain\Customer\ValueObjects\CustomerName;
use App\Domain\Customer\ValueObjects\Email;
use App\Presentation\Customer\Adapters\Inbound\Http\Resources\CustomerResourceCollection;
use Illuminate\Http\Request;

test('toArray wraps every customer through CustomerResource', function () {
    $jane = Customer::create(CustomerId::generate(), Email::fromString('jane@example.com'), CustomerName::fromString('Jane Doe'));
    $john = Customer::create(CustomerId::generate(), Email::fromString('john@example.com'), CustomerName::fromString('John Doe'));

    $array = (new CustomerResourceCollection([$jane, $john]))->toArray(new Request);

    expect($array)->toHaveCount(2)
        ->and($array[0])->toBe([
            'id' => $jane->id()->toString(),
            'email' => 'jane@example.com',
            'name' => 'Jane Doe',
        ])
        ->and($array[1])->toBe([
            'id' => $john->id()->toString(),
            'email' => 'john@example.com',
            'name' => 'John Doe',
        ]);
});

test('constructing with a non-array value fails with a TypeError', function () {
    new CustomerResourceCollection('not-an-array');
})->throws(TypeError::class);

test('constructing with an array containing a non-Customer value fails with a TypeError', function () {
    new CustomerResourceCollection(['not-a-customer']);
})->throws(TypeError::class);
