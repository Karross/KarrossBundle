<?php

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container) {
    $container->extension('framework', [
        'cache' => [
            'app' => 'cache.adapter.filesystem',
        ],
    ]);
};
