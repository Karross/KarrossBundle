<?php

namespace Integration\Formatters;

use Karross\Formatters\Boolean\YesNoFormatter;
use Karross\Formatters\FormatterResolver;
use Karross\Formatters\FormattingContext;
use Karross\Formatters\ValueFormatterInterface;
use Karross\Metadata\EntityMetadata;
use Karross\Metadata\EntityMetadataRegistry;
use Karross\Metadata\PropertyMetadata;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use TestedApp\Entity\Article;
use TestedApp\Entity\Status;
use TestedApp\Kernel;

final class ValueTranslationTest extends TestCase
{
    public function testBooleanRendersTheBundleGenericKeyOutOfTheBox(): void
    {
        [$container, $metadata] = $this->boot('test_value_translation');
        $formatter = $this->formatter($container, $metadata, 'published');

        self::assertSame('true', $formatter->format(true, $this->context('en', $metadata, 'published')));
        self::assertSame('vrai', $formatter->format(true, $this->context('fr', $metadata, 'published')));
        self::assertSame('false', $formatter->format(false, $this->context('en', $metadata, 'published')));
        self::assertSame('faux', $formatter->format(false, $this->context('fr', $metadata, 'published')));
    }

    public function testUntranslatedEnumValueFallsBackToTheRawValue(): void
    {
        [$container, $metadata] = $this->boot('test_value_translation');
        $formatter = $this->formatter($container, $metadata, 'status');

        self::assertSame('published', $formatter->format(Status::PUBLISHED, $this->context('fr', $metadata, 'status')));
    }

    public function testHostOverridesWinWithTheMostSpecificKeyFirst(): void
    {
        [$container, $metadata] = $this->boot('test_value_translation_override', [
            __DIR__.'/../TestedApp/config/translator_value_override.php',
        ]);

        $bool = $this->formatter($container, $metadata, 'published');
        $enum = $this->formatter($container, $metadata, 'status');

        // Entity-specific enum key translates the status value.
        self::assertSame('Publié', $enum->format(Status::PUBLISHED, $this->context('en', $metadata, 'status')));

        // No entity-specific key for the bool: the property-level generic applies.
        self::assertSame('Oui générique', $bool->format(true, $this->context('en', $metadata, 'published')));

        // No host key for "false": the bundle generic key still applies.
        self::assertSame('false', $bool->format(false, $this->context('en', $metadata, 'published')));
    }

    public function testYesNoFormatterTranslationsForTheConfiguredApp(): void
    {
        [$container, $metadata] = $this->boot('test_value_translation_configured', [
            __DIR__.'/../TestedApp/config/karross_with_config.php',
        ]);
        $formatter = $this->formatter($container, $metadata, 'published');

        $published = $metadata->getProperties()['published'];
        self::assertInstanceOf(PropertyMetadata::class, $published);
        self::assertSame(YesNoFormatter::class, $published->formatter);

        // Raw (ucfirst off) — the formatter applies the k_value.yes/no keys.
        // Capitalisation is a presentation concern handled by the rendering context (ucfirst).
        self::assertSame('yes', $formatter->format(true, $this->context('en', $metadata, 'published')));
        self::assertSame('oui', $formatter->format(true, $this->context('fr', $metadata, 'published')));

        // With ucfirst enabled locally on the property, the same phrase is capitalised.
        $context = $this->context('fr', $metadata, 'published')->with(ucfirst: true);
        self::assertSame('Oui', $formatter->format(true, $context));
        self::assertSame('Non', $formatter->format(false, $context));
    }

    /**
     * @param string[] $configFiles
     *
     * @return array{ContainerInterface, EntityMetadata}
     */
    private function boot(string $environment, array $configFiles = []): array
    {
        $kernel = new Kernel($environment, true, array_merge([
            __DIR__.'/../TestedApp/config/doctrine_no_shortname_entity_conflicts.php',
        ], $configFiles));
        $kernel->boot();

        $container = $kernel->getContainer()->get('test.service_container');
        self::assertInstanceOf(ContainerInterface::class, $container);

        $registry = $container->get(EntityMetadataRegistry::class);
        self::assertInstanceOf(EntityMetadataRegistry::class, $registry);

        $metadata = $registry->get(Article::class);
        self::assertInstanceOf(EntityMetadata::class, $metadata);

        return [$container, $metadata];
    }

    private function formatter(ContainerInterface $container, EntityMetadata $metadata, string $property): ValueFormatterInterface
    {
        $propertyMetadata = $metadata->getProperties()[$property];
        self::assertInstanceOf(PropertyMetadata::class, $propertyMetadata);

        $resolver = $container->get(FormatterResolver::class);
        self::assertInstanceOf(FormatterResolver::class, $resolver);

        return $resolver->get($propertyMetadata->formatter);
    }

    private function context(string $locale, EntityMetadata $metadata, string $propertyName): FormattingContext
    {
        return FormattingContext::forLocale($locale)->with(
            entitySlug: $metadata->getSlug(),
            propertyName: $propertyName,
        );
    }
}
