<?php

arch('domain has no framework dependency')
    ->expect(['App\Domain', 'App\Shared\Domain'])
    ->not->toUse(['Illuminate', 'Laravel']);

arch('presentation does not depend on infrastructure directly')
    ->expect(['App\Presentation', 'App\Shared\Presentation'])
    ->not->toUse(['App\Infrastructure', 'App\Shared\Infrastructure']);

arch('infrastructure does not depend on presentation directly')
    ->expect(['App\Infrastructure', 'App\Shared\Infrastructure'])
    ->not->toUse(['App\Presentation', 'App\Shared\Presentation']);

arch('presentation depends on application ports and commands, not concrete application services')
    ->expect('App\Presentation')
    ->toOnlyUse([
        'App\Application\Customer\Commands',
        'App\Application\Customer\Ports',
        'App\Presentation',
        'App\Shared\Presentation',
        'Illuminate',
        'response',
    ]);
