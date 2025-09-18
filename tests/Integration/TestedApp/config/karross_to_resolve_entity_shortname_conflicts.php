<?php

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $config) {
    $config->extension('karross', [
        'entities' => [
            'TestedApp\Domain\Entity\Article' => [
                'slug' => 'domain-article',
            ],
        ],
    ]);
};
