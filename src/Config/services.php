<?php

namespace Karross\Config;

use Doctrine\Persistence\ManagerRegistry;
use Karross\Routes\RouteGenerator;
use Karross\Routes\RouteLoader;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\param;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $configurator) {
    $services = $configurator->services();

    $services
    ->set(RouteGenerator::class);

    $services
    ->set(RouteLoader::class)
    ->arg('$routeGenerator', service(RouteGenerator::class))
    ->arg('$managerRegistry', service(ManagerRegistry::class))
    ->arg('$config', param('karross.config'))
    ->tag('routing.loader');
};
