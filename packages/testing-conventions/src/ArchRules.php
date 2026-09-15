<?php

namespace Platform\TestingConventions;

final class ArchRules
{
    /**
     * Every class in the given namespace must expose toDomain() and
     * toModel() -- the convention this monorepo uses for mapping between
     * a persistence model and the domain entity it backs. Call this from
     * an arch() test in the consuming service.
     */
    public static function expectMappersToImplementToDomainAndToModel(string $namespace): void
    {
        expect($namespace)->toHaveMethods(['toDomain', 'toModel']);
    }
}
