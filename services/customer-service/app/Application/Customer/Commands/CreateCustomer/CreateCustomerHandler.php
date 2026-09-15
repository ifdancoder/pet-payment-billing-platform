<?php

namespace App\Application\Customer\Commands\CreateCustomer;

use App\Application\Customer\Ports\Outbound\ICustomerRepositoryPort;
use App\Application\Customer\Ports\Outbound\IEventPublisherPort;
use App\Application\Customer\Ports\Outbound\INotificationPort;
use App\Domain\Customer\Customer;
use App\Domain\Customer\Events\CustomerCreated;
use App\Domain\Customer\ValueObjects\CustomerId;
use App\Domain\Customer\ValueObjects\CustomerName;
use App\Domain\Customer\ValueObjects\Email;

final class CreateCustomerHandler
{
    public function __construct(
        private readonly ICustomerRepositoryPort $repository,
        private readonly INotificationPort $notifier,
        private readonly IEventPublisherPort $eventPublisher,
    ) {}

    public function handle(CreateCustomerCommand $command): Customer
    {
        $customer = Customer::create(
            CustomerId::generate(),
            Email::fromString($command->email),
            CustomerName::fromString($command->name),
        );

        $this->repository->save($customer);
        $this->dispatch($customer);

        return $customer;
    }

    private function dispatch(Customer $customer): void
    {
        foreach ($customer->pullRecordedEvents() as $event) {
            $this->eventPublisher->publish($event);

            if ($event instanceof CustomerCreated) {
                $this->notifier->send(
                    $event->email,
                    'Welcome!',
                    sprintf('Hi %s, your account has been created.', $event->name),
                );
            }
        }
    }
}
