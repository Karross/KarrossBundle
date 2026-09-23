<?php

namespace Karross\Config;

use Karross\Responders\ResponderInterface;
use Karross\Responders\ResponderManager;
use Karross\Twig\EntityTemplateResolver;
use Karross\Twig\FieldLabelExtension;
use Karross\Twig\HtmlLocaleExtension;
use Karross\Twig\PropertyAccessorExtension;
use Karross\Twig\PropertyTemplateResolver;
use Karross\Twig\StringableExtension;
use Karross\Twig\UrlBuilderExtension;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;
use function Symfony\Component\DependencyInjection\Loader\Configurator\tagged_iterator;

return static function (ContainerConfigurator $configurator): void {
    $services = $configurator->services();
    $services->defaults()->autowire()->autoconfigure();

    // Responders
    $services
        ->load('Karross\\Responders\\', __DIR__.'/../Responders/*')
        ->tag('karross.responder');

    $services
        ->set(ResponderManager::class)
        ->arg('$responders', tagged_iterator('karross.responder', ResponderInterface::class));

    // Twig
    $services->set(EntityTemplateResolver::class);
    $services->set(PropertyTemplateResolver::class);
    $services->set(StringableExtension::class);
    $services->set(PropertyAccessorExtension::class);

    $services
        ->set(FieldLabelExtension::class)
        ->arg('$translator', service(TranslatorInterface::class));

    $services
        ->set(HtmlLocaleExtension::class)
        ->arg('$requestStack', service(RequestStack::class));

    $services->set(UrlBuilderExtension::class);
};
