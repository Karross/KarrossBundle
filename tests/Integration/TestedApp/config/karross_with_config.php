<?php

use Karross\Formatters\Boolean\YesNoFormatter;
use Karross\Formatters\IntlCurrencyFormatter;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use TestedApp\Entity\Article;

return static function (ContainerConfigurator $config) {
    $config->extension('framework', [
        'default_locale' => 'en',
        'enabled_locales' => ['en', 'fr'],
        'translator' => [
            'enabled' => true,
            'fallbacks' => ['en'],
        ],
    ]);

    $config->extension('karross', [
        'routes' => [
            'prefix' => 'dashboard',
            'index' => '/{_locale}/{prefix}/{slug}',
            'show' => '/{_locale}/{prefix}/{slug}/{identifiers}',
        ],
        'entities' => [
            Article::class => [
                'properties' => [
                    'price' => [
                        'formatter' => IntlCurrencyFormatter::class,
                        'formatter_options' => ['currency' => 'EUR'],
                    ],
                    'published' => [
                        'formatter' => YesNoFormatter::class,
                        'formatter_options' => ['ucfirst' => true],
                    ],
                ],
            ],
        ],
    ]);
};
