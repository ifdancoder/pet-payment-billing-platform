<?php

namespace App\Presentation\Http\V1\Controllers;

use App\Application\Auth\DataTransferObjects\TokenPair;
use App\Application\Auth\Ports\Inbound\IAuthServicePort;
use App\Presentation\Http\V1\Requests\LoginRequest;
use App\Presentation\Http\V1\Requests\LogoutRequest;
use App\Presentation\Http\V1\Requests\RefreshTokenRequest;
use App\Presentation\Http\V1\Requests\RegisterAccountRequest;
use App\Shared\Presentation\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

final class AuthController extends Controller
{
    public function __construct(private readonly IAuthServicePort $auth) {}

    public function register(RegisterAccountRequest $request): JsonResponse
    {
        $tokens = $this->auth->register(
            $request->string('email')->toString(),
            $request->string('password')->toString(),
            $request->string('merchant_name')->toString(),
        );

        return response()->json($this->tokenResponse($tokens), 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $tokens = $this->auth->login(
            $request->string('email')->toString(),
            $request->string('password')->toString(),
            $request->string('merchant_id')->toString(),
        );

        return response()->json($this->tokenResponse($tokens));
    }

    public function refresh(RefreshTokenRequest $request): JsonResponse
    {
        return response()->json($this->tokenResponse(
            $this->auth->refresh($request->string('refresh_token')->toString()),
        ));
    }

    public function logout(LogoutRequest $request): JsonResponse
    {
        $this->auth->logout($request->string('refresh_token')->toString());

        return response()->json(null, 204);
    }

    /** @return array<string, int|string> */
    private function tokenResponse(TokenPair $tokens): array
    {
        return [
            'token_type' => 'Bearer',
            'access_token' => $tokens->accessToken,
            'refresh_token' => $tokens->refreshToken,
            'expires_in' => $tokens->expiresIn,
            'user_id' => $tokens->userId,
            'merchant_id' => $tokens->merchantId,
            'role' => $tokens->role,
        ];
    }
}
