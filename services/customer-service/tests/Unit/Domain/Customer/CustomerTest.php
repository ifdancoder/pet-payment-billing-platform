<?php

use App\Domain\Customer\Customer;
use App\Domain\Customer\Events\CustomerCreated;
use App\Domain\Customer\ValueObjects\CustomerId;
use App\Domain\Customer\ValueObjects\CustomerName;
use App\Domain\Customer\ValueObjects\Email;

test('create exposes the given id, email and name', function () {
    $id = CustomerId::generate();
    $email = Email::fromString('jane@example.com');
    $name = CustomerName::fromString('Jane Doe');

    $customer = Customer::create($id, $email, $name);

    expect($customer->id()->equals($id))->toBeTrue()
        ->and($customer->email()->equals($email))->toBeTrue()
        ->and($customer->name()->equals($name))->toBeTrue();
});

test('create records a CustomerCreated event carrying the same data', function () {
    $id = CustomerId::generate();
    $email = Email::fromString('jane@example.com');
    $name = CustomerName::fromString('Jane Doe');

    $customer = Customer::create($id, $email, $name);
    $events = $customer->pullRecordedEvents();

    expect($events)->toHaveCount(1);
    expect($events[0])->toBeInstanceOf(CustomerCreated::class);
    expect($events[0]->customerId->equals($id))->toBeTrue();
    expect($events[0]->email->equals($email))->toBeTrue();
    expect($events[0]->name->equals($name))->toBeTrue();
});

test('pullRecordedEvents empties the recorded events', function () {
    $customer = Customer::create(CustomerId::generate(), Email::fromString('jane@example.com'), CustomerName::fromString('Jane Doe'));

    $customer->pullRecordedEvents();

    expect($customer->pullRecordedEvents())->toBe([]);
});
