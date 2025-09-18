<?php

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    $container->extension('doctrine', [
        'dbal' => [
            'driver' => 'pdo_sqlite',
            'url'    => 'sqlite:///:memory:',
        ],
        'orm' => [
            'auto_generate_proxy_classes' => true,
            'naming_strategy'             => 'doctrine.orm.naming_strategy.underscore_number_aware',
            'auto_mapping'                => false,
            'mappings' => [
                'TestedApp' => [
                    'is_bundle' => false,
                    'type'      => 'attribute',
                    'dir'       => '%kernel.project_dir%/tests/Integration/TestedApp/Entity',
                    'prefix'    => 'TestedApp\Entity',
                    'alias'     => 'TestedApp',
                ],
            ],
        ],
    ]);
};

