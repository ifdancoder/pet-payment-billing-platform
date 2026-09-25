<?php

namespace App\Presentation\Http\V1\Controllers;

use App\Application\ApiKey\ApiKeyService;
use App\Presentation\Http\V1\Requests\CreateApiKeyRequest;
use App\Presentation\Http\V1\Requests\ExchangeApiKeyRequest;
use App\Presentation\Http\V1\Resources\ApiKeyResource;
use App\Shared\Presentation\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class ApiKeyController extends Controller
{
    public function __construct(private readonly ApiKeyService $apiKeys) {}

    public function index(string $merchant): AnonymousResourceCollection
    {
        return ApiKeyResource::collection($this->apiKeys->list($merchant));
    }

    public function store(CreateApiKeyRequest $request, string $merchant): JsonResponse
    {
        $created = $this->apiKeys->create($merchant, $request->string('name')->toString(), $request->string('role', 'developer')->toString(), $request->validated('scopes', []));
        $resource = ApiKeyResource::make($created['api_key'])->resolve($request);

        return response()->json(['data' => [...$resource, 'secret' => $created['secret']]], 201);
    }

    public function destroy(string $merchant, string $apiKey): JsonResponse
    {
        $this->apiKeys->revoke($merchant, $apiKey);
        return response()->json(null, 204);
    }

    public function exchange(ExchangeApiKeyRequest $request): JsonResponse
    {
        return response()->json($this->apiKeys->exchange($request->string('api_key')->toString()));
    }
}
