<?php

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $config) {
    $config->extension('karross', [
        'routes' => [
            'prefix' => 'dashboard',
            'index' => '/{prefix}/{slug}',
        ],
    ]);
};
