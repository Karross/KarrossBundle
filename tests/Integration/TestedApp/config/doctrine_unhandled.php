<?php

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    $container->extension('doctrine', [
        'dbal' => [
            'driver' => 'pdo_sqlite',
            'url' => 'sqlite:///:memory:',
        ],
        'orm' => [
            'auto_generate_proxy_classes' => true,
            'enable_native_lazy_objects' => true,
            'naming_strategy' => 'doctrine.orm.naming_strategy.underscore_number_aware',
            'auto_mapping' => false,
            'mappings' => [
                'TestedAppUnhandled' => [
                    'is_bundle' => false,
                    'type' => 'attribute',
                    'dir' => __DIR__.'/../Unhandled/Entity',
                    'prefix' => 'TestedApp\Unhandled\Entity',
                    'alias' => 'TestedAppUnhandled',
                ],
            ],
        ],
    ]);
};
