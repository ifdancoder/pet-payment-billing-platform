<?php

use App\Domain\Customer\Customer;
use App\Domain\Customer\ValueObjects\CustomerId;
use App\Domain\Customer\ValueObjects\CustomerName;
use App\Domain\Customer\ValueObjects\Email;
use App\Presentation\Customer\Adapters\Inbound\Http\Resources\CustomerResource;
use Illuminate\Http\Request;

test('toArray exposes the customer id, email and name', function () {
    $id = CustomerId::generate();
    $customer = Customer::create($id, Email::fromString('jane@example.com'), CustomerName::fromString('Jane Doe'));

    $array = (new CustomerResource($customer))->toArray(new Request);

    expect($array)->toBe([
        'id' => $id->toString(),
        'email' => 'jane@example.com',
        'name' => 'Jane Doe',
    ]);
});
