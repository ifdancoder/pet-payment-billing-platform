<?php

use App\Application\Customer\Commands\CreateCustomer\CreateCustomerCommand;
use App\Application\Customer\Commands\CreateCustomer\CreateCustomerHandler;
use App\Application\Customer\Ports\Outbound\ICustomerRepositoryPort;
use App\Infrastructure\Customer\Adapters\Notification\GenericNotificationMail;
use App\Shared\Application\Ports\Outbound\IOutboxPort;
use Illuminate\Support\Facades\Mail;

test('handle persists a new customer with the given email and name', function () {
    Mail::fake();
    $handler = app(CreateCustomerHandler::class);

    $customer = $handler->handle(new CreateCustomerCommand('jane@example.com', 'Jane Doe'));

    $persisted = app(ICustomerRepositoryPort::class)->get($customer->id());
    expect($persisted->email()->toString())->toBe('jane@example.com')
        ->and($persisted->name()->toString())->toBe('Jane Doe');
});

test('handle sends a welcome notification to the new customer', function () {
    Mail::fake();
    $handler = app(CreateCustomerHandler::class);

    $handler->handle(new CreateCustomerCommand('jane@example.com', 'Jane Doe'));

    Mail::assertSent(
        GenericNotificationMail::class,
        fn (GenericNotificationMail $mail) => $mail->hasTo('jane@example.com'),
    );
});

test('handle records a CustomerCreated integration event in the outbox', function () {
    Mail::fake();
    $handler = app(CreateCustomerHandler::class);

    $customer = $handler->handle(new CreateCustomerCommand('jane@example.com', 'Jane Doe'));

    $unpublished = app(IOutboxPort::class)->unpublished();
    expect($unpublished)->toHaveCount(1)
        ->and($unpublished[0]->eventType)->toBe('customer.created.v1')
        ->and($unpublished[0]->aggregateId)->toBe($customer->id()->toString())
        ->and($unpublished[0]->payload)->toBe([
            'customer_id' => $customer->id()->toString(),
            'email' => 'jane@example.com',
            'name' => 'Jane Doe',
        ]);
});
