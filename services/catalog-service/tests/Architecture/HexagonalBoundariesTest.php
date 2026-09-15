<?php

use Platform\TestingConventions\ArchRules;

arch('persistence mappers implement toDomain and toModel', function (): void {
    ArchRules::expectMappersToImplementToDomainAndToModel(
        'App\Infrastructure\Product\Adapters\Persistence\Mappers',
    );
    ArchRules::expectMappersToImplementToDomainAndToModel(
        'App\Infrastructure\Price\Adapters\Persistence\Mappers',
    );
});

arch('domain has no framework dependency')
    ->expect(['App\Domain', 'App\Shared\Domain'])
    ->not->toUse(['Illuminate', 'Laravel']);

arch('presentation does not depend on infrastructure directly')
    ->expect(['App\Presentation', 'App\Shared\Presentation'])
    ->not->toUse(['App\Infrastructure', 'App\Shared\Infrastructure']);

arch('infrastructure does not depend on presentation directly')
    ->expect(['App\Infrastructure', 'App\Shared\Infrastructure'])
    ->not->toUse(['App\Presentation', 'App\Shared\Presentation']);

arch('presentation depends on application ports, commands, queries and domain entities, not concrete application services')
    ->expect('App\Presentation')
    ->toOnlyUse([
        'App\Application\Product\Commands',
        'App\Application\Product\Queries',
        'App\Application\Product\Ports',
        'App\Domain',
        'App\Presentation',
        'App\Shared\Presentation',
        'Illuminate',
        'response',
    ]);
