<?php

namespace Platform\TestingConventions;

final class ArchRules
{
    /** Requires persistence mappers to expose toDomain() and toModel(). */
    public static function expectMappersToImplementToDomainAndToModel(string $namespace): void
    {
        expect($namespace)->toHaveMethods(['toDomain', 'toModel']);
    }
}
