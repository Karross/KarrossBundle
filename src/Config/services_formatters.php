<?php

namespace Karross\Config;

use CommerceGuys\Intl\Currency\CurrencyRepository;
use CommerceGuys\Intl\NumberFormat\NumberFormatRepository;
use Karross\Formatters\Boolean\TrueFalseFormatter;
use Karross\Formatters\Boolean\YesNoFormatter;
use Karross\Formatters\DateTime\DateFormatter;
use Karross\Formatters\DateTime\DateTimeFormatter;
use Karross\Formatters\DateTime\TimeFormatter;
use Karross\Formatters\EnumFormatter;
use Karross\Formatters\FormatterResolver;
use Karross\Formatters\FormattingContextBuilder;
use Karross\Formatters\IntlCurrencyFormatter;
use Karross\Formatters\IntlNumberFormatter;
use Karross\Formatters\NotAvailableFormatter;
use Karross\Formatters\Options\NamedDatetimeFormats;
use Karross\Formatters\Resolvers\BooleanFormatterResolver;
use Karross\Formatters\Resolvers\FloatFormatterResolver;
use Karross\Formatters\Resolvers\IntegerFormatterResolver;
use Karross\Formatters\Resolvers\StringFormatterResolver;
use Karross\Formatters\StringFormatter;
use Karross\Formatters\ValueTranslator;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $configurator): void {
    $services = $configurator->services();
    $services->defaults()->autowire()->autoconfigure();

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
        ->set(IntegerFormatterResolver::class)
        ->set(FloatFormatterResolver::class)
        ->set(StringFormatterResolver::class)
        ->set(ValueTranslator::class)
        ->set(FormattingContextBuilder::class);

    $services
        ->set(FormatterResolver::class)
        ->arg('$formatters', \Symfony\Component\DependencyInjection\Loader\Configurator\tagged_iterator('karross.formatter'))
        ->arg('$resolvers', \Symfony\Component\DependencyInjection\Loader\Configurator\tagged_iterator('karross.formatter.resolver'));

    $services
        ->set(NamedDatetimeFormats::class)
        ->arg('$config', \Symfony\Component\DependencyInjection\Loader\Configurator\service(KarrossConfig::class));
};
