<?php

namespace App\Domain\Merchant;

use App\Domain\Merchant\Events\MerchantCreated;
use App\Domain\Merchant\Events\MerchantDisabled;
use App\Domain\Merchant\ValueObjects\MerchantId;
use App\Domain\Merchant\ValueObjects\MerchantName;
use App\Domain\Merchant\ValueObjects\MerchantStatus;

final class Merchant
{
    /** @var array<object> */
    private array $recordedEvents = [];

    private function __construct(
        private readonly MerchantId $id,
        private MerchantName $name,
        private MerchantStatus $status,
    ) {}

    public static function create(MerchantId $id, MerchantName $name): self
    {
        $merchant = new self($id, $name, MerchantStatus::Active);
        $merchant->recordEvent(new MerchantCreated($id, $name));

        return $merchant;
    }

    /**
     * Rebuilds a Merchant from already-persisted data. Unlike create(),
     * this does not record a MerchantCreated event.
     */
    public static function reconstitute(MerchantId $id, MerchantName $name, MerchantStatus $status): self
    {
        return new self($id, $name, $status);
    }

    public function id(): MerchantId
    {
        return $this->id;
    }

    public function name(): MerchantName
    {
        return $this->name;
    }

    public function status(): MerchantStatus
    {
        return $this->status;
    }

    /**
     * Disabling an already-Disabled merchant is a silent no-op rather
     * than an error: this is an administrative action that may be
     * retried or applied more than once without it being a bug.
     */
    public function disable(): void
    {
        if ($this->status === MerchantStatus::Disabled) {
            return;
        }

        $this->status = MerchantStatus::Disabled;
        $this->recordEvent(new MerchantDisabled($this->id));
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
