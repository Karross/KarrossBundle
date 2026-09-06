<?php

namespace Integration\Formatters;

use CommerceGuys\Intl\Currency\CurrencyRepository;
use CommerceGuys\Intl\NumberFormat\NumberFormatRepository;
use Karross\Formatters\FormattingContext;
use Karross\Formatters\IntlCurrencyFormatter;
use Karross\Metadata\EntityMetadataRegistry;
use Karross\Metadata\PropertyMetadata;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Container;
use TestedApp\Entity\Article;
use TestedApp\Kernel;

final class CurrencyFormatterConfigTest extends TestCase
{
    public function testPropertyFormatterOptionsFlowFromConfigToMetadata(): void
    {
        $kernel = new Kernel('test_currency_formatter', true, [
            __DIR__.'/../TestedApp/config/doctrine_no_shortname_entity_conflicts.php',
            __DIR__.'/../TestedApp/config/karross_currency_formatter.php',
        ]);
        $kernel->boot();

        $container = $kernel->getContainer()->get('test.service_container');
        self::assertInstanceOf(Container::class, $container);

        $registry = $container->get(EntityMetadataRegistry::class);
        self::assertInstanceOf(EntityMetadataRegistry::class, $registry);

        $price = $registry->get(Article::class)->getProperties()['price'];
        self::assertInstanceOf(PropertyMetadata::class, $price);
        self::assertSame(IntlCurrencyFormatter::class, $price->formatter);
        self::assertSame(['currency' => 'USD', 'ucfirst' => false], $price->formatterOptions);
    }

    public function testCurrencyFormatterRendersCurrencyFromTheContext(): void
    {
        $formatter = new IntlCurrencyFormatter(
            new NumberFormatRepository(),
            new CurrencyRepository(),
        );

        self::assertStringContainsString('19,90', $formatter->format('19.90', FormattingContext::forLocale('fr', 'USD')));
        self::assertStringContainsString('$US', $formatter->format('19.90', FormattingContext::forLocale('fr', 'USD')));
        $en = $formatter->format('19.90', FormattingContext::forLocale('en', 'EUR'));
        self::assertStringContainsString('€', $en);
        self::assertStringContainsString('.', $en);
    }
}
