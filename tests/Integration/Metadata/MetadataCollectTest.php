<?php

namespace Integration\Metadata;

use Karross\Formatters\Boolean\TrueFalseFormatter;
use Karross\Formatters\DateTime\DateFormatter;
use Karross\Formatters\DateTime\DateTimeFormatter;
use Karross\Formatters\EnumFormatter;
use Karross\Formatters\IntlNumberFormatter;
use Karross\Formatters\NotAvailableFormatter;
use Karross\Formatters\StringFormatter;
use Karross\Metadata\Computed\EntityMetadataRegistry;
use Karross\Metadata\Computed\FieldMetadata;
use Karross\Metadata\Computed\PropertyMetadata;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Container;
use TestedApp\Entity\Article;
use TestedApp\Entity\Category;
use TestedApp\Kernel;
use TestedApp\TemplateOverride\Kernel as TemplateOverrideKernel;
use TestedApp\Unhandled\Entity\Item;

final class MetadataCollectTest extends TestCase
{
    private static ?EntityMetadataRegistry $registry = null;

    public function testArticleFactsAreGatheredWithoutProjection(): void
    {
        $expected = [
            'id' => IntlNumberFormatter::class,
            'title' => StringFormatter::class,
            'content' => StringFormatter::class,
            'published' => TrueFalseFormatter::class,
            'premium' => TrueFalseFormatter::class,
            'viewCount' => IntlNumberFormatter::class,
            'sortOrder' => IntlNumberFormatter::class,
            'bigCounter' => IntlNumberFormatter::class,
            'price' => IntlNumberFormatter::class,
            'createdAt' => DateTimeFormatter::class,
            'publishedAt' => DateTimeFormatter::class,
            'scheduledDate' => DateFormatter::class,
            'status' => EnumFormatter::class,
            'tags' => NotAvailableFormatter::class,
        ];

        $fields = $this->fields(Article::class);

        foreach ($expected as $name => $formatter) {
            $property = $fields[$name];
            self::assertInstanceOf(FieldMetadata::class, $property, $name);
            self::assertSame($formatter, $property->formatter, $name.'.formatter');
            self::assertSame(['index' => '@Karross/index/field.html.twig'], $property->templates, $name.'.templates');
        }
    }

    public function testUnknownTypeNeverBreaksTheBuildAndResolvesToNotAvailable(): void
    {
        $kernel = new Kernel('test_metadata_unhandled', true, [
            __DIR__.'/../TestedApp/config/doctrine_unhandled.php',
        ]);
        $kernel->boot();

        $container = $kernel->getContainer()->get('test.service_container');
        self::assertInstanceOf(Container::class, $container);

        $registry = $container->get(EntityMetadataRegistry::class);
        self::assertInstanceOf(EntityMetadataRegistry::class, $registry);

        $properties = $registry->get(Item::class)->getProperties();
        self::assertSame(NotAvailableFormatter::class, $properties['labels']->formatter);
    }

    public function testTypeSpecificHostOverrideIsResolvedIntoPropertyMetadata(): void
    {
        $kernel = new TemplateOverrideKernel('test_metadata_override', true, [
            __DIR__.'/../TestedApp/config/doctrine_standard.php',
        ]);
        $kernel->boot();

        $container = $kernel->getContainer()->get('test.service_container');
        self::assertInstanceOf(Container::class, $container);

        $registry = $container->get(EntityMetadataRegistry::class);
        self::assertInstanceOf(EntityMetadataRegistry::class, $registry);

        $properties = $registry->get(Article::class)->getProperties();

        self::assertSame(
            ['index' => '@Karross/index/field_type_datetime.html.twig'],
            $properties['createdAt']->templates,
        );
        self::assertSame(
            ['index' => '@Karross/index/field.html.twig'],
            $properties['title']->templates,
        );
    }

    public function testEntityTemplatesAreResolvedAtBuild(): void
    {
        $metadata = $this->registry()->get(Article::class);

        self::assertSame([
            'index' => '@Karross/index/index.html.twig',
            'items' => '@Karross/index/items.html.twig',
            'no_items' => '@Karross/index/no_items.html.twig',
            'item' => '@Karross/index/item.html.twig',
        ], $metadata->templates['index']);
    }

    public function testEntitySpecificPageOverrideIsResolvedAtBuild(): void
    {
        $kernel = new TemplateOverrideKernel('test_metadata_entity_override', true, [
            __DIR__.'/../TestedApp/config/doctrine_standard.php',
        ]);
        $kernel->boot();

        $container = $kernel->getContainer()->get('test.service_container');
        self::assertInstanceOf(Container::class, $container);

        $registry = $container->get(EntityMetadataRegistry::class);
        self::assertInstanceOf(EntityMetadataRegistry::class, $registry);

        $article = $registry->get(Article::class)->templates['index'];
        self::assertSame('@Karross/index/index.html.twig', $article['index']);
        self::assertSame('@Karross/index/items_entity_article.html.twig', $article['items']);
        self::assertSame('@Karross/index/no_items.html.twig', $article['no_items']);
        self::assertSame('@Karross/index/item.html.twig', $article['item']);

        $category = $registry->get(Category::class)->templates['index'];
        self::assertSame('@Karross/index/items.html.twig', $category['items']);
    }

    public function testCategoryFacts(): void
    {
        $fields = $this->fields(Category::class);

        self::assertSame(['index' => '@Karross/index/field.html.twig'], $fields['id']->templates);
        self::assertSame(IntlNumberFormatter::class, $fields['id']->formatter);

        self::assertSame(['index' => '@Karross/index/field.html.twig'], $fields['name']->templates);
        self::assertSame(StringFormatter::class, $fields['name']->formatter);
    }

    /**
     * @return array<PropertyMetadata>
     */
    private function fields(string $fqcn): array
    {
        $registry = $this->registry();

        return $registry->get($fqcn)->getProperties();
    }

    private function registry(): EntityMetadataRegistry
    {
        if (null !== self::$registry) {
            return self::$registry;
        }

        $kernel = new Kernel('test_metadata_collect', true, [
            __DIR__.'/../TestedApp/config/doctrine_standard.php',
        ]);
        $kernel->boot();

        $container = $kernel->getContainer()->get('test.service_container');
        self::assertInstanceOf(Container::class, $container);

        $registry = $container->get(EntityMetadataRegistry::class);
        self::assertInstanceOf(EntityMetadataRegistry::class, $registry);
        self::$registry = $registry;

        return $registry;
    }
}
