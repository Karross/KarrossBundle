<?php

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $config) {
    $config->extension('karross', [
        'datetime_formats' => [
            'k_medium_date' => [
                'fr' => 'd MMMM yyyy',
            ],
        ],
    ]);
};
