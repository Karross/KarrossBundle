<?php

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use TestedApp\Entity\Article;

return static function (ContainerConfigurator $config) {
    $config->extension('karross', [
        'entities' => [
            Article::class => [
                'properties' => [
                    'price' => [
                        'formatter_options' => [
                            'invalid_key' => 'value',
                        ],
                    ],
                ],
            ],
        ],
    ]);
};
