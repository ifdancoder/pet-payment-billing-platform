<?php

namespace App\Domain\User;

use App\Domain\User\Events\UserDisabled;
use App\Domain\User\Events\UserRegistered;
use App\Domain\User\ValueObjects\Email;
use App\Domain\User\ValueObjects\UserId;
use App\Domain\User\ValueObjects\UserStatus;

final class User
{
    /** @var array<object> */
    private array $recordedEvents = [];

    private function __construct(
        private readonly UserId $id,
        private readonly Email $email,
        private string $passwordHash,
        private UserStatus $status,
    ) {}

    /**
     * $passwordHash is already a hash by the time it reaches here —
     * hashing is a PasswordHasher (Application port) concern, never
     * something this aggregate does itself.
     */
    public static function register(UserId $id, Email $email, string $passwordHash): self
    {
        $user = new self($id, $email, $passwordHash, UserStatus::Active);
        $user->recordEvent(new UserRegistered($id, $email));

        return $user;
    }

    /**
     * Rebuilds a User from already-persisted data. Unlike register(),
     * this does not record a UserRegistered event.
     */
    public static function reconstitute(UserId $id, Email $email, string $passwordHash, UserStatus $status): self
    {
        return new self($id, $email, $passwordHash, $status);
    }

    public function id(): UserId
    {
        return $this->id;
    }

    public function email(): Email
    {
        return $this->email;
    }

    public function passwordHash(): string
    {
        return $this->passwordHash;
    }

    public function status(): UserStatus
    {
        return $this->status;
    }

    public function isActive(): bool
    {
        return $this->status === UserStatus::Active;
    }

    public function changePasswordHash(string $passwordHash): void
    {
        $this->passwordHash = $passwordHash;
    }

    /**
     * Disabling an already-Disabled user is a silent no-op rather than
     * an error, matching Merchant::disable() — an administrative action
     * that may be retried or applied more than once without that being
     * a bug.
     */
    public function disable(): void
    {
        if ($this->status === UserStatus::Disabled) {
            return;
        }

        $this->status = UserStatus::Disabled;
        $this->recordEvent(new UserDisabled($this->id));
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
