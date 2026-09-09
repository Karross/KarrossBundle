<?php

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $config) {
    $config->extension('framework', [
        'default_locale' => 'en',
        'enabled_locales' => ['en', 'fr'],
        'translator' => [
            'enabled' => true,
            'fallbacks' => ['en'],
        ],
    ]);
};
