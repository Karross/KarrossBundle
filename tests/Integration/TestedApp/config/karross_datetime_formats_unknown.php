<?php

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use TestedApp\Entity\Article;

return static function (ContainerConfigurator $config) {
    $config->extension('karross', [
        'entities' => [
            Article::class => [
                'properties' => [
                    'createdAt' => [
                        'formatter_options' => [
                            'datetime_format' => 'does_not_exist',
                        ],
                    ],
                ],
            ],
        ],
    ]);
};
