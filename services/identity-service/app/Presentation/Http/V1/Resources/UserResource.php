<?php

namespace App\Presentation\Http\V1\Resources;

use App\Domain\User\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class UserResource extends JsonResource
{
    public function __construct(private readonly User $user)
    {
        parent::__construct($user);
    }

    /**
     * Never exposes the password hash — this resource is the only place
     * a User crosses the process boundary, so leaving it out here is
     * what keeps it from ever reaching an HTTP response.
     *
     * @return array<string, string|null>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->user->id()->toString(),
            'email' => $this->user->email()->toString(),
            'status' => $this->user->status()->label(),
        ];
    }
}
