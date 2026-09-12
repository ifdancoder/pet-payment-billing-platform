<?php

namespace App\Domain\Customer;

use App\Domain\Customer\Events\CustomerCreated;
use App\Domain\Customer\ValueObjects\CustomerId;
use App\Domain\Customer\ValueObjects\CustomerName;
use App\Domain\Customer\ValueObjects\Email;

final class Customer
{
    /** @var array<object> */
    private array $recordedEvents = [];

    private function __construct(
        private readonly CustomerId $id,
        private readonly Email $email,
        private readonly CustomerName $name,
    ) {}

    public static function create(CustomerId $id, Email $email, CustomerName $name): self
    {
        $customer = new self($id, $email, $name);
        $customer->recordEvent(new CustomerCreated($id, $email, $name));

        return $customer;
    }

    /**
     * Rebuilds a Customer from already-persisted data. Unlike create(), this
     * does not record a CustomerCreated event.
     */
    public static function reconstitute(CustomerId $id, Email $email, CustomerName $name): self
    {
        return new self($id, $email, $name);
    }

    public function id(): CustomerId
    {
        return $this->id;
    }

    public function email(): Email
    {
        return $this->email;
    }

    public function name(): CustomerName
    {
        return $this->name;
    }

    /**
     * @return array<object>
     */
    public function pullRecordedEvents(): array
    {
        $events = $this->recordedEvents;
        $this->recordedEvents = [];

        return $events;
    }

    private function recordEvent(object $event): void
    {
        $this->recordedEvents[] = $event;
    }
}
