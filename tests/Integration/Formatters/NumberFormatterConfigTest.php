<?php

namespace Integration\Formatters;

use Karross\Formatters\IntlNumberFormatter;
use Karross\Metadata\Computed\EntityMetadataRegistry;
use Karross\Metadata\Computed\FieldMetadata;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Container;
use TestedApp\Entity\Article;
use TestedApp\Kernel;

final class NumberFormatterConfigTest extends TestCase
{
    public function testScaleFromDoctrineColumnSetsMaximumFractionDigits(): void
    {
        $registry = $this->registry();
        $price = $registry->get(Article::class)->getProperties()['price'];

        self::assertInstanceOf(FieldMetadata::class, $price);
        self::assertSame(IntlNumberFormatter::class, $price->formatter);
        self::assertSame(2, $price->formatterOptions['maximum_fraction_digits']);
    }

    public function testMinimumFractionDigitsFromConfigOverridesDefault(): void
    {
        $kernel = new Kernel('test_number_options', true, [
            __DIR__.'/../TestedApp/config/doctrine_standard.php',
            __DIR__.'/../TestedApp/config/karross_number_options.php',
        ]);
        $kernel->boot();

        $container = $kernel->getContainer()->get('test.service_container');
        self::assertInstanceOf(Container::class, $container);

        $registry = $container->get(EntityMetadataRegistry::class);
        self::assertInstanceOf(EntityMetadataRegistry::class, $registry);

        $price = $registry->get(Article::class)->getProperties()['price'];
        self::assertInstanceOf(FieldMetadata::class, $price);
        self::assertSame(IntlNumberFormatter::class, $price->formatter);
        self::assertSame(2, $price->formatterOptions['minimum_fraction_digits']);
        self::assertSame(2, $price->formatterOptions['maximum_fraction_digits']);
    }

    public function testMaximumFractionDigitsFromConfigOverridesScale(): void
    {
        $kernel = new Kernel('test_number_max_override', true, [
            __DIR__.'/../TestedApp/config/doctrine_standard.php',
            __DIR__.'/../TestedApp/config/karross_number_options.php',
        ]);
        $kernel->boot();

        $container = $kernel->getContainer()->get('test.service_container');
        self::assertInstanceOf(Container::class, $container);

        $registry = $container->get(EntityMetadataRegistry::class);
        self::assertInstanceOf(EntityMetadataRegistry::class, $registry);

        $viewCount = $registry->get(Article::class)->getProperties()['viewCount'];
        self::assertInstanceOf(FieldMetadata::class, $viewCount);
        self::assertSame(0, $viewCount->formatterOptions['maximum_fraction_digits']);
    }

    public function testScaleAutoWireIsSkippedWhenConfigProvidesOptions(): void
    {
        $kernel = new Kernel('test_number_skip_auto', true, [
            __DIR__.'/../TestedApp/config/doctrine_standard.php',
            __DIR__.'/../TestedApp/config/karross_number_options.php',
        ]);
        $kernel->boot();

        $container = $kernel->getContainer()->get('test.service_container');
        self::assertInstanceOf(Container::class, $container);

        $registry = $container->get(EntityMetadataRegistry::class);
        self::assertInstanceOf(EntityMetadataRegistry::class, $registry);

        $price = $registry->get(Article::class)->getProperties()['price'];
        self::assertSame(2, $price->formatterOptions['maximum_fraction_digits']);
    }

    public function testInvalidFormatterOptionsAreRejected(): void
    {
        $kernel = new Kernel('test_number_invalid', true, [
            __DIR__.'/../TestedApp/config/doctrine_standard.php',
            __DIR__.'/../TestedApp/config/karross_number_invalid_options.php',
        ]);

        $this->expectException(\Symfony\Component\Config\Definition\Exception\InvalidConfigurationException::class);

        $kernel->boot();
    }

    private function registry(): EntityMetadataRegistry
    {
        $kernel = new Kernel('test_number_formatter', true, [
            __DIR__.'/../TestedApp/config/doctrine_standard.php',
        ]);
        $kernel->boot();

        $container = $kernel->getContainer()->get('test.service_container');
        self::assertInstanceOf(Container::class, $container);

        $registry = $container->get(EntityMetadataRegistry::class);
        self::assertInstanceOf(EntityMetadataRegistry::class, $registry);

        return $registry;
    }
}
