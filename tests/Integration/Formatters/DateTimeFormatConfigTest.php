<?php

namespace Integration\Formatters;

use Innmind\BlackBox\PHPUnit\BlackBox;
use Innmind\BlackBox\Set;
use Karross\Config\KarrossConfig;
use Karross\Formatters\FormatterResolver;
use Karross\Formatters\FormattingContextBuilder;
use Karross\Formatters\Options\NamedDatetimeFormats;
use Karross\Metadata\Computed\EntityMetadataRegistry;
use Karross\Metadata\Computed\FieldMetadata;
use Karross\Metadata\Computed\PropertyMetadata;
use Karross\Twig\PropertyAccessorExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use TestedApp\Entity\Article;
use TestedApp\Kernel;

final class DateTimeFormatConfigTest extends TestCase
{
    use BlackBox;

    public function testExplicitFormatEmbedsResolvedPatterns(): void
    {
        $createdAt = $this->properties('test_datetime_formats', [
            __DIR__.'/../TestedApp/config/karross_datetime_formats.php',
        ])['createdAt'];

        self::assertInstanceOf(FieldMetadata::class, $createdAt);
        self::assertSame(
            [
                'patterns' => [
                    'fr' => "d MMMM yyyy 'à' HH:mm",
                    'en' => "MMMM d, yyyy 'at' HH:mm",
                    'default' => 'yyyy-MM-dd HH:mm',
                ],
            ],
            $createdAt->formatterOptions['datetime_format'],
        );
    }

    public function testDatePropertyWithoutOptionEmbedsImplicitMediumDatePreset(): void
    {
        $fields = $this->properties('test_datetime_formats_default');

        $scheduledDate = $fields['scheduledDate'];
        self::assertInstanceOf(FieldMetadata::class, $scheduledDate);
        self::assertSame(
            ['presets' => ['date' => 'medium']],
            $scheduledDate->formatterOptions['datetime_format'],
        );

        $createdAt = $fields['createdAt'];
        self::assertInstanceOf(FieldMetadata::class, $createdAt);
        self::assertSame(
            ['presets' => ['date' => 'medium', 'time' => 'short']],
            $createdAt->formatterOptions['datetime_format'],
        );

        $title = $fields['title'];
        self::assertInstanceOf(FieldMetadata::class, $title);
        self::assertArrayNotHasKey('datetime_format', $title->formatterOptions);
    }

    public function testHostOverrideOfBuiltInNameReplacesImplicitDateEmbedding(): void
    {
        $fields = $this->properties('test_datetime_formats_override', [
            __DIR__.'/../TestedApp/config/karross_datetime_formats_override.php',
        ]);

        $scheduledDate = $fields['scheduledDate'];
        self::assertInstanceOf(FieldMetadata::class, $scheduledDate);
        self::assertSame(
            [
                'patterns' => ['fr' => 'd MMMM yyyy'],
                'presets' => ['date' => 'medium'],
            ],
            $scheduledDate->formatterOptions['datetime_format'],
        );
    }

    public function testStringFormDatetimeFormatIsAccepted(): void
    {
        $kernel = new Kernel('test_datetime_formats_string_form', true, [
            __DIR__.'/../TestedApp/config/doctrine_standard.php',
            __DIR__.'/../TestedApp/config/karross_datetime_formats.php',
        ]);
        $kernel->boot();

        $container = $kernel->getContainer()->get('test.service_container');
        self::assertInstanceOf(Container::class, $container);

        $config = $container->get(KarrossConfig::class);
        self::assertInstanceOf(KarrossConfig::class, $config);
        self::assertSame(['default' => 'yyyy-MM-dd'], $config->datetimeFormats()['string_form_format']);

        $registry = $container->get(EntityMetadataRegistry::class);
        self::assertInstanceOf(EntityMetadataRegistry::class, $registry);
        self::assertArrayHasKey('createdAt', $registry->get(Article::class)->getProperties());
    }

    public function testUnknownExplicitFormatThrowsAtBuild(): void
    {
        $kernel = new Kernel('test_datetime_formats_unknown', true, [
            __DIR__.'/../TestedApp/config/doctrine_standard.php',
            __DIR__.'/../TestedApp/config/karross_datetime_formats_unknown.php',
        ]);
        $kernel->boot();

        $container = $kernel->getContainer()->get('test.service_container');
        self::assertInstanceOf(Container::class, $container);
        $registry = $container->get(EntityMetadataRegistry::class);
        self::assertInstanceOf(EntityMetadataRegistry::class, $registry);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown datetime format "does_not_exist" for property "createdAt" of "TestedApp\Entity\Article".');

        $registry->get(Article::class);
    }

    public function testRuntimeLocaleFallsBackExactThenLanguageThenDefault(): void
    {
        $boot = $this->boot('test_datetime_formats_runtime', [
            __DIR__.'/../TestedApp/config/karross_datetime_formats.php',
        ]);
        $createdAt = $boot['properties']['createdAt'];
        self::assertInstanceOf(FieldMetadata::class, $createdAt);

        $article = new Article()
            ->setTitle('Runtime')
            ->setCreatedAt(new \DateTimeImmutable('2026-09-14 15:30:00'));

        self::assertSame(
            '14 septembre 2026 à 15:30',
            $this->render($boot['container'], $article, $createdAt, 'fr'),
        );
        self::assertSame(
            'September 14, 2026 at 15:30',
            $this->render($boot['container'], $article, $createdAt, 'en'),
        );
        // fr_CA has no exact key → language fr
        self::assertSame(
            '14 septembre 2026 à 15:30',
            $this->render($boot['container'], $article, $createdAt, 'fr_CA'),
        );
        // de has neither exact nor language → default
        self::assertSame(
            '2026-09-14 15:30',
            $this->render($boot['container'], $article, $createdAt, 'de'),
        );
    }

    /**
     * Property-based: any locale × any known format name resolves and formats
     * a fixed date without falling back to N/A.
     */
    public function testLocaleTimesFormatNameAlwaysFormats(): void
    {
        $boot = $this->boot('test_datetime_formats_property', [
            __DIR__.'/../TestedApp/config/karross_datetime_formats.php',
        ]);
        $createdAt = $boot['properties']['createdAt'];
        self::assertInstanceOf(FieldMetadata::class, $createdAt);
        $article = new Article()
            ->setTitle('Property')
            ->setCreatedAt(new \DateTimeImmutable('2026-09-14 15:30:00'));

        $formats = new NamedDatetimeFormats(new KarrossConfig([
            'datetime_formats' => [
                'my_custom_datetime_format' => [
                    'fr' => "d MMMM yyyy 'à' HH:mm",
                    'en' => "MMMM d, yyyy 'at' HH:mm",
                    'default' => 'yyyy-MM-dd HH:mm',
                ],
                'string_form_format' => 'yyyy-MM-dd',
            ],
        ]));

        $locales = ['fr', 'en', 'fr_CA', 'de', 'pt_BR', 'en_GB'];
        $names = array_keys(NamedDatetimeFormats::DEFAULT_FORMATS);
        $names[] = 'my_custom_datetime_format';
        $names[] = 'string_form_format';

        $this->forAll(
            self::indexOf($locales),
            self::indexOf($names),
        )->then(function (string $locale, string $name) use ($boot, $article, $createdAt, $formats): void {
            $resolved = $formats->configFor($name);
            self::assertNotNull($resolved, $name);

            $property = new FieldMetadata(
                name: $createdAt->name,
                fqcn: $createdAt->fqcn,
                formatter: $createdAt->formatter,
                formatterOptions: ['datetime_format' => $resolved],
                templates: $createdAt->templates,
                entitySlug: $createdAt->entitySlug,
            );

            $output = $this->render($boot['container'], $article, $property, $locale);
            self::assertNotSame('N/A', $output, "$locale × $name");
            self::assertNotSame('', $output, "$locale × $name");
        });
    }

    /**
     * @param array<int, string> $values
     *
     * @return Set<string>
     */
    private static function indexOf(array $values): Set
    {
        $count = \count($values);

        return Set::integers()->map(
            static fn (int $i): string => $values[abs($i) % $count],
        );
    }

    /**
     * @param string[] $configFiles
     *
     * @return array{container: Container, properties: array<string, PropertyMetadata>}
     */
    private function boot(string $environment, array $configFiles = []): array
    {
        $kernel = new Kernel($environment, true, array_merge([
            __DIR__.'/../TestedApp/config/doctrine_standard.php',
        ], $configFiles));
        $kernel->boot();

        $container = $kernel->getContainer()->get('test.service_container');
        self::assertInstanceOf(Container::class, $container);

        $registry = $container->get(EntityMetadataRegistry::class);
        self::assertInstanceOf(EntityMetadataRegistry::class, $registry);

        return [
            'container' => $container,
            'properties' => $registry->get(Article::class)->getProperties(),
        ];
    }

    /**
     * @param string[] $configFiles
     *
     * @return array<string, PropertyMetadata>
     */
    private function properties(string $environment, array $configFiles = []): array
    {
        return $this->boot($environment, $configFiles)['properties'];
    }

    private function render(Container $container, Article $article, FieldMetadata $property, string $locale): ?string
    {
        $resolver = $container->get(FormatterResolver::class);
        self::assertInstanceOf(FormatterResolver::class, $resolver);

        $requestStack = new RequestStack();
        $request = new Request();
        $request->setLocale($locale);
        $requestStack->push($request);

        $extension = new PropertyAccessorExtension($resolver, new FormattingContextBuilder($requestStack));

        return $extension->getFormattedValue($article, $property);
    }
}
