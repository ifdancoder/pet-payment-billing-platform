<?php

use App\Infrastructure\ApiKey\Providers\ApiKeyServiceProvider;
use App\Infrastructure\Auth\Providers\AuthServiceProvider;
use App\Infrastructure\Membership\Providers\MembershipServiceProvider;
use App\Infrastructure\Merchant\Providers\MerchantServiceProvider;
use App\Infrastructure\User\Providers\UserServiceProvider;
use App\Shared\Infrastructure\Providers\AppServiceProvider;

return [
    AppServiceProvider::class,
    AuthServiceProvider::class,
    MerchantServiceProvider::class,
    UserServiceProvider::class,
    MembershipServiceProvider::class,
    ApiKeyServiceProvider::class,
];
