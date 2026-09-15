<?php

use App\Application\Customer\Commands\CreateCustomer\CreateCustomerCommand;
use App\Application\Customer\Commands\CreateCustomer\CreateCustomerHandler;
use App\Application\Customer\Ports\Outbound\ICustomerRepositoryPort;
use App\Domain\Customer\Events\CustomerCreated;
use App\Infrastructure\Customer\Adapters\Notification\GenericNotificationMail;
use Illuminate\Support\Facades\Log;
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

test('handle publishes a CustomerCreated event', function () {
    Mail::fake();
    Log::spy();
    $handler = app(CreateCustomerHandler::class);

    $handler->handle(new CreateCustomerCommand('jane@example.com', 'Jane Doe'));

    Log::shouldHaveReceived('info')
        ->once()
        ->withArgs(fn (string $message) => str_contains($message, CustomerCreated::class));
});
