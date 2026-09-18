<?php

namespace App\Infrastructure\Membership\Providers;

use App\Application\Membership\Ports\Outbound\IMembershipRepositoryPort;
use App\Infrastructure\Membership\Adapters\Persistence\Repositories\EloquentMembershipRepository;
use App\Infrastructure\Membership\Providers\V1\MembershipServiceProvider as MembershipServiceProviderV1;
use Illuminate\Support\ServiceProvider;

class MembershipServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(IMembershipRepositoryPort::class, EloquentMembershipRepository::class);

        // API bindings are version-scoped: each supported version registers
        // its own provider under Providers/{Version}. Only V1 exists today;
        // adding V2 means a new sibling provider, never touching this one.
        $this->app->register(MembershipServiceProviderV1::class);
    }
}
