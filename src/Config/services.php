<?php

namespace Karross\Config;

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $configurator) {
    $services = $configurator->services();

    $services
        ->defaults()
        ->autowire()
        ->autoconfigure();

    /** @var \Closure(ContainerConfigurator): void $formatters */
    $formatters = require __DIR__.'/services_formatters.php';
    $formatters($configurator);

    /** @var \Closure(ContainerConfigurator): void $metadata */
    $metadata = require __DIR__.'/services_metadata.php';
    $metadata($configurator);

    /** @var \Closure(ContainerConfigurator): void $render */
    $render = require __DIR__.'/services_render.php';
    $render($configurator);

    // Actions
    $services
        ->load('Karross\\Actions\\', __DIR__.'/../Actions/*')
        ->exclude([__DIR__.'/../Actions/ActionContext.php'])
        ->tag('controller.service_arguments');

    // Pages (global pages, outside the per-entity action flow)
    $services
        ->load('Karross\\Pages\\', __DIR__.'/../Pages/*')
        ->tag('controller.service_arguments');
};
