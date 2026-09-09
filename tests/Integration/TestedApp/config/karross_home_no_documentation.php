<?php

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    $container->extension('karross', [
        'home' => [
            'show_documentation' => false,
        ],
    ]);
};
