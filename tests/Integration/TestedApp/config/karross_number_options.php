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
                            'minimum_fraction_digits' => 2,
                            'maximum_fraction_digits' => 2,
                        ],
                    ],
                    'viewCount' => [
                        'formatter_options' => [
                            'maximum_fraction_digits' => 0,
                        ],
                    ],
                ],
            ],
        ],
    ]);
};
