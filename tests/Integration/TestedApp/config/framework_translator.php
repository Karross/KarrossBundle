<?php

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $config) {
    $config->extension('framework', [
        'translator' => [
            'paths' => [__DIR__.'/../translations_value_override'],
        ],
    ]);
};
