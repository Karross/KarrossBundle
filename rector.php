<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Php84\Rector\Foreach_\ForeachToArrayAllRector;
use Rector\Php84\Rector\Foreach_\ForeachToArrayAnyRector;
use Rector\Symfony\Set\SymfonySetList;

return RectorConfig::configure()
    ->withPaths([__DIR__.'/src', __DIR__.'/tests'])
    ->withPhpSets(php85: true)
    ->withSets([SymfonySetList::SYMFONY_CODE_QUALITY])
    ->withAttributesSets(symfony: true)
    ->withSkip([
        // array_any()/array_all() need PHP 8.6: the bundle floor is PHP 8.5.
        ForeachToArrayAllRector::class,
        ForeachToArrayAnyRector::class,
    ])
    ->withCache(__DIR__.'/var/rector');
