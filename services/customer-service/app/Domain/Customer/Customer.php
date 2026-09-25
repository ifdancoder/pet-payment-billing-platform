<?php

namespace App\Domain\Customer;

use App\Domain\Customer\Events\CustomerCreated;
use App\Domain\Customer\ValueObjects\CustomerId;
use App\Domain\Customer\ValueObjects\CustomerName;
use App\Domain\Customer\ValueObjects\Email;
use App\Shared\Domain\ValueObjects\MerchantId;

final class Customer
{
    /** @var array<object> */
    private array $recordedEvents = [];

    private function __construct(
        private readonly CustomerId $id,
        private readonly MerchantId $merchantId,
        private Email $email,
        private CustomerName $name,
    ) {}

    public static function create(CustomerId $id, MerchantId $merchantId, Email $email, CustomerName $name): self
    {
        $customer = new self($id, $merchantId, $email, $name);
        $customer->recordEvent(new CustomerCreated($id, $merchantId, $email, $name));

        return $customer;
    }

    /**
     * Rebuilds a Customer from already-persisted data. Unlike create(), this
     * does not record a CustomerCreated event.
     */
    public static function reconstitute(CustomerId $id, MerchantId $merchantId, Email $email, CustomerName $name): self
    {
        return new self($id, $merchantId, $email, $name);
    }

    public function id(): CustomerId
    {
        return $this->id;
    }

    public function merchantId(): MerchantId
    {
        return $this->merchantId;
    }

    public function email(): Email
    {
        return $this->email;
    }

    public function name(): CustomerName
    {
        return $this->name;
    }

    public function changeEmail(Email $email): void
    {
        $this->email = $email;
    }

    public function rename(CustomerName $name): void
    {
        $this->name = $name;
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
