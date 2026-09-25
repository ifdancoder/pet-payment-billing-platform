<?php

namespace App\Presentation\Http\V1\Controllers;

use App\Application\Membership\Commands\AddMembership\AddMembershipCommand;
use App\Application\Membership\Commands\ChangeMembershipRole\ChangeMembershipRoleCommand;
use App\Application\Membership\Commands\RemoveMembership\RemoveMembershipCommand;
use App\Application\Membership\Ports\Inbound\IMembershipServicePort;
use App\Application\Membership\Queries\ListMemberships\ListMembershipsQuery;
use App\Domain\Membership\ValueObjects\Role;
use App\Presentation\Http\V1\Requests\AddMembershipRequest;
use App\Presentation\Http\V1\Requests\ChangeMembershipRoleRequest;
use App\Presentation\Http\V1\Resources\MembershipResource;
use App\Shared\Presentation\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class MembershipController extends Controller
{
    public function __construct(private readonly IMembershipServicePort $memberships) {}

    public function index(string $merchant): AnonymousResourceCollection
    {
        return MembershipResource::collection($this->memberships->listMemberships(new ListMembershipsQuery($merchant)));
    }

    public function store(AddMembershipRequest $request, string $merchant): JsonResponse
    {
        $membership = $this->memberships->addMembership(new AddMembershipCommand(
            $request->string('user_id')->toString(),
            $merchant,
            self::role($request->string('role')->toString())->value,
        ));

        return MembershipResource::make($membership)->response()->setStatusCode(201);
    }

    public function update(ChangeMembershipRoleRequest $request, string $merchant, string $membership): MembershipResource
    {
        return MembershipResource::make($this->memberships->changeMembershipRole(new ChangeMembershipRoleCommand(
            $merchant,
            $membership,
            self::role($request->string('role')->toString())->value,
        )));
    }

    public function destroy(string $merchant, string $membership): JsonResponse
    {
        $this->memberships->removeMembership(new RemoveMembershipCommand($merchant, $membership));

        return response()->json(null, 204);
    }

    private static function role(string $role): Role
    {
        return match ($role) {
            'owner' => Role::Owner,
            'admin' => Role::Admin,
            'developer' => Role::Developer,
            'finance' => Role::Finance,
            'viewer' => Role::Viewer,
        };
    }
}
