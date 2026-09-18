<?php

namespace App\Application\Membership\Queries\ListMemberships;

final class ListMembershipsQuery
{
    public function __construct(public readonly string $merchantId) {}
}
