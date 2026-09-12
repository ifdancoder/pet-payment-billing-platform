<?php

use App\Application\Customer\Commands\CreateCustomer\CreateCustomerCommand;
use App\Application\Customer\Commands\CreateCustomer\CreateCustomerHandler;
use App\Application\Customer\Ports\Outbound\ICustomerRepositoryPort;
use App\Application\Customer\Ports\Outbound\INotificationPort;
use App\Infrastructure\Customer\Adapters\Notification\GenericNotificationMail;
use Illuminate\Support\Facades\Mail;

test('handle persists a new customer with the given email and name', function () {
    Mail::fake();
    $handler = new CreateCustomerHandler(app(ICustomerRepositoryPort::class), app(INotificationPort::class));

    $customer = $handler->handle(new CreateCustomerCommand('jane@example.com', 'Jane Doe'));

    $persisted = app(ICustomerRepositoryPort::class)->get($customer->id());
    expect($persisted->email()->toString())->toBe('jane@example.com')
        ->and($persisted->name()->toString())->toBe('Jane Doe');
});

test('handle sends a welcome notification to the new customer', function () {
    Mail::fake();
    $handler = new CreateCustomerHandler(app(ICustomerRepositoryPort::class), app(INotificationPort::class));

    $handler->handle(new CreateCustomerCommand('jane@example.com', 'Jane Doe'));

    Mail::assertSent(
        GenericNotificationMail::class,
        fn (GenericNotificationMail $mail) => $mail->hasTo('jane@example.com'),
    );
});
