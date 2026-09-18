<?php

namespace App\Domain\Membership;

use App\Domain\Membership\Events\MembershipAdded;
use App\Domain\Membership\Events\MembershipRoleChanged;
use App\Domain\Membership\ValueObjects\MembershipId;
use App\Domain\Membership\ValueObjects\Role;
use App\Domain\Merchant\ValueObjects\MerchantId;
use App\Domain\User\ValueObjects\UserId;

/**
 * The association between a User and a Merchant — a User may hold a
 * Membership in more than one Merchant, each with its own Role. Unlike
 * Merchant/User, a Membership has no status of its own: it exists or it
 * doesn't, so removal is a hard delete rather than a state transition.
 */
final class Membership
{
    /** @var array<object> */
    private array $recordedEvents = [];

    private function __construct(
        private readonly MembershipId $id,
        private readonly UserId $userId,
        private readonly MerchantId $merchantId,
        private Role $role,
    ) {}

    public static function create(MembershipId $id, UserId $userId, MerchantId $merchantId, Role $role): self
    {
        $membership = new self($id, $userId, $merchantId, $role);
        $membership->recordEvent(new MembershipAdded($id, $userId, $merchantId, $role));

        return $membership;
    }

    /**
     * Rebuilds a Membership from already-persisted data. Unlike
     * create(), this does not record a MembershipAdded event.
     */
    public static function reconstitute(MembershipId $id, UserId $userId, MerchantId $merchantId, Role $role): self
    {
        return new self($id, $userId, $merchantId, $role);
    }

    public function id(): MembershipId
    {
        return $this->id;
    }

    public function userId(): UserId
    {
        return $this->userId;
    }

    public function merchantId(): MerchantId
    {
        return $this->merchantId;
    }

    public function role(): Role
    {
        return $this->role;
    }

    public function changeRole(Role $role): void
    {
        if ($this->role === $role) {
            return;
        }

        $previousRole = $this->role;
        $this->role = $role;

        $this->recordEvent(new MembershipRoleChanged($this->id, $previousRole, $role));
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
