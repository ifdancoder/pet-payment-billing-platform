<?php

namespace App\Application\Customer\Commands\CreateCustomer;

use App\Application\Customer\IntegrationEvents\CustomerCreatedIntegrationEvent;
use App\Application\Customer\Ports\Outbound\ICustomerRepositoryPort;
use App\Application\Customer\Ports\Outbound\INotificationPort;
use App\Domain\Customer\Customer;
use App\Domain\Customer\Events\CustomerCreated;
use App\Domain\Customer\ValueObjects\CustomerId;
use App\Domain\Customer\ValueObjects\CustomerName;
use App\Domain\Customer\ValueObjects\Email;
use App\Shared\Application\Ports\Outbound\IOutboxPort;
use App\Shared\Application\Ports\Outbound\ITransactionManagerPort;

final class CreateCustomerHandler
{
    public function __construct(
        private readonly ICustomerRepositoryPort $repository,
        private readonly INotificationPort $notifier,
        private readonly IOutboxPort $outbox,
        private readonly ITransactionManagerPort $transaction,
    ) {}

    public function handle(CreateCustomerCommand $command): Customer
    {
        $customer = Customer::create(
            CustomerId::generate(),
            Email::fromString($command->email),
            CustomerName::fromString($command->name),
        );

        $this->transaction->run(function () use ($customer): void {
            $this->repository->save($customer);
            $this->recordIntegrationEvents($customer);
        });

        $this->notifier->send(
            $customer->email(),
            'Welcome!',
            sprintf('Hi %s, your account has been created.', $customer->name()),
        );

        return $customer;
    }

    private function recordIntegrationEvents(Customer $customer): void
    {
        foreach ($customer->pullRecordedEvents() as $event) {
            if ($event instanceof CustomerCreated) {
                $this->outbox->add(CustomerCreatedIntegrationEvent::fromDomainEvent($event));
            }
        }
    }
}
