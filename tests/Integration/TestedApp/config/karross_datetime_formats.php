<?php

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use TestedApp\Entity\Article;

return static function (ContainerConfigurator $config) {
    $config->extension('karross', [
        'datetime_formats' => [
            'my_custom_datetime_format' => [
                'fr' => "d MMMM yyyy 'à' HH:mm",
                'en' => "MMMM d, yyyy 'at' HH:mm",
                'default' => 'yyyy-MM-dd HH:mm',
            ],
            'string_form_format' => 'yyyy-MM-dd',
        ],
        'entities' => [
            Article::class => [
                'properties' => [
                    'createdAt' => [
                        'formatter_options' => [
                            'datetime_format' => 'my_custom_datetime_format',
                        ],
                    ],
                ],
            ],
        ],
    ]);
};
