<?php

namespace Karross\Config;

use Doctrine\Persistence\ManagerRegistry;
use Karross\Metadata\EntityMetadataBuilder;
use Karross\Metadata\EntityMetadataRegistry;
use Karross\Responders\ResponderInterface;
use Karross\Responders\ResponderManager;
use Karross\Responders\Transformers\ValueFormatter;
use Karross\Routes\RouteGenerator;
use Karross\Routes\RouteLoader;
use Karross\Twig\FieldLabelExtension;
use Karross\Twig\HtmlLocaleExtension;
use Karross\Twig\PropertyAccessorExtension;
use Karross\Twig\StringableExtension;
use Karross\Twig\TemplateRegistry;
use Karross\Twig\TemplateRegistryExtension;
use Karross\Twig\TemplateResolver;
use Karross\Twig\UrlBuilderExtension;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use function Symfony\Component\DependencyInjection\Loader\Configurator\param;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;
use function Symfony\Component\DependencyInjection\Loader\Configurator\tagged_iterator;

return static function (ContainerConfigurator $configurator) {
    $services = $configurator->services();

    $services
        ->defaults()
        ->autowire()
        ->autoconfigure();

    // Actions
    $services
        ->load('Karross\\Actions\\', __DIR__ . '/../Actions/*')
        ->exclude([__DIR__.'/../Actions/ActionContext.php'])
        ->tag('controller.service_arguments');

    // Config
    $services
        ->set(KarrossConfig::class)
        ->arg('$config', param('karross.config'));

    // Metadata
    $services
        ->set(EntityMetadataBuilder::class)
        ->arg('$managerRegistry', service(ManagerRegistry::class))
        ->arg('$config', service(KarrossConfig::class));

    $services
        ->set(EntityMetadataRegistry::class)
        ->arg('$cache', service(CacheInterface::class))
        ->arg('$builder', service(EntityMetadataBuilder::class));

    // Responders
    $services
        ->load('Karross\\Responders\\', __DIR__ . '/../Responders/*')
        ->tag('karross.responder');

    $services
        ->set(ResponderManager::class)
        ->arg('$responders', tagged_iterator('karross.responder', ResponderInterface::class));

        // Transformers
        $services->set(ValueFormatter::class);

    // Routes
    $services->set(RouteGenerator::class);

    $services
        ->set(RouteLoader::class)
        ->arg('$routeGenerator', service(RouteGenerator::class))
        ->arg('$entityMetadataRegistry', service(EntityMetadataRegistry::class))
        ->tag('routing.loader');

    // Twig
    $services->set(TemplateResolver::class);

    $services
        ->set(TemplateRegistry::class)
        ->arg('$cache', service(CacheInterface::class))
        ->arg('$templateResolver', service(TemplateResolver::class));

    $services->set(StringableExtension::class);

    $services->set(PropertyAccessorExtension::class);

    $services
        ->set(FieldLabelExtension::class)
        ->arg('$translator', service(TranslatorInterface::class));

    $services
        ->set(HtmlLocaleExtension::class)
        ->arg('$requestStack', service(RequestStack::class));

    $services
        ->set(TemplateRegistryExtension::class)
        ->arg('$templateRegistry', service(TemplateRegistry::class));

    $services->set(UrlBuilderExtension::class);
};
