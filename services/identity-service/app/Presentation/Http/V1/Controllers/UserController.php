<?php

namespace App\Presentation\Http\V1\Controllers;

use App\Application\User\Commands\RegisterUser\RegisterUserCommand;
use App\Application\User\Ports\Inbound\IUserServicePort;
use App\Presentation\Http\V1\Requests\RegisterUserRequest;
use App\Presentation\Http\V1\Resources\UserResource;
use App\Shared\Presentation\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

final class UserController extends Controller
{
    public function __construct(private readonly IUserServicePort $userService) {}

    public function store(RegisterUserRequest $request): JsonResponse
    {
        $user = $this->userService->registerUser(new RegisterUserCommand(
            $request->validated('email'),
            $request->validated('password'),
        ));

        return UserResource::make($user)->response()->setStatusCode(201);
    }
}
