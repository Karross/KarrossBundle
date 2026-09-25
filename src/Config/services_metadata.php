<?php

namespace Karross\Config;

use Doctrine\Persistence\ManagerRegistry;
use Karross\Formatters\FormatterResolver;
use Karross\Formatters\Options\DateTimeOptionsResolver;
use Karross\Formatters\Options\NamedDatetimeFormats;
use Karross\Metadata\Collect\AssociationMetadataBuilder;
use Karross\Metadata\Collect\EntityMetadataBuilder;
use Karross\Metadata\Collect\EntitySlugResolver;
use Karross\Metadata\Collect\EntityTemplateResolverInterface;
use Karross\Metadata\Collect\FieldMetadataBuilder;
use Karross\Metadata\Collect\PropertyTemplateResolverInterface;
use Karross\Metadata\Computed\EntityMetadataRegistry;
use Karross\Routes\RouteGenerator;
use Karross\Routes\RouteLoader;
use Karross\Routes\RoutePattern;
use Karross\Twig\EntityTemplateResolver;
use Karross\Twig\PropertyTemplateResolver;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Contracts\Cache\CacheInterface;

use function Symfony\Component\DependencyInjection\Loader\Configurator\param;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $configurator): void {
    $services = $configurator->services();
    $services->defaults()->autowire()->autoconfigure();

    // Config
    $services
        ->set(KarrossConfig::class)
        ->arg('$config', param('karross.config'));

    $services
        ->set(EntityConfig::class)
        ->arg('$config', param('karross.config'));

    // Metadata
    $services->alias(PropertyTemplateResolverInterface::class, PropertyTemplateResolver::class);
    $services->alias(EntityTemplateResolverInterface::class, EntityTemplateResolver::class);

    $services
        ->set(EntitySlugResolver::class)
        ->arg('$entityConfig', service(EntityConfig::class));

    $services
        ->set(DateTimeOptionsResolver::class)
        ->arg('$formats', service(NamedDatetimeFormats::class));

    $services
        ->set(FieldMetadataBuilder::class)
        ->arg('$entityConfig', service(EntityConfig::class))
        ->arg('$formatterResolver', service(FormatterResolver::class))
        ->arg('$datetimeOptionsResolver', service(DateTimeOptionsResolver::class))
        ->arg('$propertyTemplateResolver', service(PropertyTemplateResolver::class));

    $services
        ->set(AssociationMetadataBuilder::class)
        ->arg('$managerRegistry', service(ManagerRegistry::class))
        ->arg('$entityConfig', service(EntityConfig::class))
        ->arg('$formatterResolver', service(FormatterResolver::class))
        ->arg('$datetimeOptionsResolver', service(DateTimeOptionsResolver::class))
        ->arg('$propertyTemplateResolver', service(PropertyTemplateResolver::class));

    $services
        ->set(EntityMetadataBuilder::class)
        ->arg('$managerRegistry', service(ManagerRegistry::class))
        ->arg('$slugResolver', service(EntitySlugResolver::class))
        ->arg('$fields', service(FieldMetadataBuilder::class))
        ->arg('$associations', service(AssociationMetadataBuilder::class))
        ->arg('$entityTemplateResolver', service(EntityTemplateResolver::class));

    $services
        ->set(EntityMetadataRegistry::class)
        ->arg('$cache', service(CacheInterface::class))
        ->arg('$builder', service(EntityMetadataBuilder::class))
        ->arg('$debug', param('kernel.debug'));

    // Routes (loader needs EntityMetadataRegistry)
    $services->set(RoutePattern::class)->autowire();

    $services
        ->set(RouteGenerator::class)
        ->autowire();

    $services
        ->set(RouteLoader::class)
        ->arg('$routeGenerator', service(RouteGenerator::class))
        ->arg('$entityMetadataRegistry', service(EntityMetadataRegistry::class))
        ->tag('routing.loader');
};
