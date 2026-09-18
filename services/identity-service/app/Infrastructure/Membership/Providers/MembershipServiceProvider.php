<?php

namespace App\Infrastructure\Membership\Providers;

use App\Application\Membership\MembershipService;
use App\Application\Membership\Ports\Inbound\IMembershipServicePort;
use App\Application\Membership\Ports\Outbound\IMembershipRepositoryPort;
use App\Infrastructure\Membership\Adapters\Persistence\Repositories\EloquentMembershipRepository;
use App\Infrastructure\Membership\Providers\V1\MembershipServiceProvider as MembershipServiceProviderV1;
use Illuminate\Support\ServiceProvider;

class MembershipServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(IMembershipRepositoryPort::class, EloquentMembershipRepository::class);
        $this->app->bind(IMembershipServicePort::class, MembershipService::class);

        $this->app->register(MembershipServiceProviderV1::class);
    }
}
