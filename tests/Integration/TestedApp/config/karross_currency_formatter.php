<?php

use Karross\Formatters\IntlCurrencyFormatter;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use TestedApp\Entity\Article;

return static function (ContainerConfigurator $config) {
    $config->extension('karross', [
        'entities' => [
            Article::class => [
                'properties' => [
                    'price' => [
                        'formatter' => IntlCurrencyFormatter::class,
                        'formatter_options' => ['currency' => 'USD'],
                    ],
                ],
            ],
        ],
    ]);
};
