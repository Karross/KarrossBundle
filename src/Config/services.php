<?php

namespace Karross\Config;

use CommerceGuys\Intl\Currency\CurrencyRepository;
use CommerceGuys\Intl\NumberFormat\NumberFormatRepository;
use Doctrine\Persistence\ManagerRegistry;
use Karross\Formatters\Boolean\TrueFalseFormatter;
use Karross\Formatters\Boolean\YesNoFormatter;
use Karross\Formatters\DateTime\DateFormatter;
use Karross\Formatters\DateTime\DateTimeFormatter;
use Karross\Formatters\DateTime\TimeFormatter;
use Karross\Formatters\EnumFormatter;
use Karross\Formatters\FormatterResolver;
use Karross\Formatters\IntlCurrencyFormatter;
use Karross\Formatters\IntlNumberFormatter;
use Karross\Formatters\NotAvailableFormatter;
use Karross\Formatters\Resolvers\BooleanFormatterResolver;
use Karross\Formatters\StringFormatter;
use Karross\Formatters\ValueTranslator;
use Karross\Metadata\Collect\ComputedMetadataBuilder;
use Karross\Metadata\Collect\EntityTemplateResolverInterface;
use Karross\Metadata\Collect\PropertyTemplateResolverInterface;
use Karross\Metadata\Computed\EntityMetadataRegistry;
use Karross\Responders\ResponderInterface;
use Karross\Responders\ResponderManager;
use Karross\Routes\RouteGenerator;
use Karross\Routes\RouteLoader;
use Karross\Routes\RoutePattern;
use Karross\Twig\EntityTemplateResolver;
use Karross\Twig\FieldLabelExtension;
use Karross\Twig\HtmlLocaleExtension;
use Karross\Twig\PropertyAccessorExtension;
use Karross\Twig\PropertyTemplateResolver;
use Karross\Twig\StringableExtension;
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
        ->load('Karross\\Actions\\', __DIR__.'/../Actions/*')
        ->exclude([__DIR__.'/../Actions/ActionContext.php'])
        ->tag('controller.service_arguments');

    // Pages (global pages, outside the per-entity action flow)
    $services
        ->load('Karross\\Pages\\', __DIR__.'/../Pages/*')
        ->tag('controller.service_arguments');

    // Config
    $services
        ->set(KarrossConfig::class)
        ->arg('$config', param('karross.config'));

    // Formatters
    $services
        ->set(NumberFormatRepository::class)
        ->set(CurrencyRepository::class)
        ->set(StringFormatter::class)
        ->set(TrueFalseFormatter::class)
        ->set(YesNoFormatter::class)
        ->set(IntlNumberFormatter::class)
        ->set(IntlCurrencyFormatter::class)
        ->set(EnumFormatter::class)
        ->set(DateFormatter::class)
        ->set(TimeFormatter::class)
        ->set(DateTimeFormatter::class)
        ->set(NotAvailableFormatter::class)
        ->set(BooleanFormatterResolver::class)
        ->set(ValueTranslator::class);

    $services
        ->set(FormatterResolver::class)
        ->arg('$formatters', tagged_iterator('karross.formatter'))
        ->arg('$resolvers', tagged_iterator('karross.formatter.resolver'));

    // Metadata
    $services->alias(PropertyTemplateResolverInterface::class, PropertyTemplateResolver::class);
    $services->alias(EntityTemplateResolverInterface::class, EntityTemplateResolver::class);

    $services
        ->set(ComputedMetadataBuilder::class)
        ->arg('$managerRegistry', service(ManagerRegistry::class))
        ->arg('$config', service(KarrossConfig::class))
        ->arg('$formatterResolver', service(FormatterResolver::class))
        ->arg('$propertyTemplateResolver', service(PropertyTemplateResolver::class))
        ->arg('$entityTemplateResolver', service(EntityTemplateResolver::class));

    $services
        ->set(EntityMetadataRegistry::class)
        ->arg('$cache', service(CacheInterface::class))
        ->arg('$builder', service(ComputedMetadataBuilder::class))
        ->arg('$debug', param('kernel.debug'));

    // Responders
    $services
        ->load('Karross\\Responders\\', __DIR__.'/../Responders/*')
        ->tag('karross.responder');

    $services
        ->set(ResponderManager::class)
        ->arg('$responders', tagged_iterator('karross.responder', ResponderInterface::class));

    // Routes
    $services->set(RoutePattern::class);
    $services->set(RouteGenerator::class);

    $services
        ->set(RouteLoader::class)
        ->arg('$routeGenerator', service(RouteGenerator::class))
        ->arg('$entityMetadataRegistry', service(EntityMetadataRegistry::class))
        ->tag('routing.loader');

    // Twig
    $services->set(EntityTemplateResolver::class);
    $services->set(PropertyTemplateResolver::class);

    $services->set(StringableExtension::class);

    $services
        ->set(PropertyAccessorExtension::class);

    $services
        ->set(FieldLabelExtension::class)
        ->arg('$translator', service(TranslatorInterface::class));

    $services
        ->set(HtmlLocaleExtension::class)
        ->arg('$requestStack', service(RequestStack::class));

    $services->set(UrlBuilderExtension::class);
};
