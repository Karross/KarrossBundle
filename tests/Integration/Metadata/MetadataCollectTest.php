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
use TestedApp\Entity\Status;
use TestedApp\Kernel;

final class MetadataCollectTest extends TestCase
{
    private static ?EntityMetadataRegistry $registry = null;

    public function testArticleFactsAreGatheredWithoutProjection(): void
    {
        $expected = [
            'id' => ['int', 'integer', false, IntlNumberFormatter::class, ['integer', 'int', 'number']],
            'title' => ['string', 'string', false, StringFormatter::class, ['string']],
            'content' => ['string', 'text', false, StringFormatter::class, ['text', 'string']],
            'published' => ['bool', 'boolean', false, TrueFalseFormatter::class, ['boolean', 'bool']],
            'viewCount' => ['int', 'integer', false, IntlNumberFormatter::class, ['integer', 'int', 'number']],
            'price' => ['string', 'decimal', true, StringFormatter::class, ['decimal', 'string']],
            'createdAt' => [\DateTimeImmutable::class, 'datetime_immutable', false, DateTimeFormatter::class, ['datetime']],
            'publishedAt' => [\DateTimeImmutable::class, 'datetime_immutable', false, DateTimeFormatter::class, ['datetime']],
            'scheduledDate' => [\DateTimeImmutable::class, 'date_immutable', false, DateFormatter::class, ['date', 'datetime']],
            'status' => [Status::class, 'string', false, EnumFormatter::class, ['enum', 'string']],
            'tags' => ['array', 'json', false, NotAvailableFormatter::class, ['json', 'array']],
        ];

        $fields = $this->fields(Article::class);

        foreach ($expected as $name => [$phpType, $doctrineType, $conflict, $formatter, $typeHierarchy]) {
            $property = $fields[$name];
            self::assertInstanceOf(FieldMetadata::class, $property, $name);
            self::assertSame($phpType, $property->phpType, $name.'.phpType');
            self::assertSame($doctrineType, $property->doctrineType, $name.'.doctrineType');
            self::assertSame($conflict, $property->conflict, $name.'.conflict');
            self::assertSame($formatter, $property->formatter, $name.'.formatter');
            self::assertSame($typeHierarchy, $property->typeHierarchy, $name.'.typeHierarchy');
            self::assertNull($property->widget, $name.'.widget');
        }
    }

    public function testColumnDetailsAreCollected(): void
    {
        $fields = $this->fields(Article::class);

        self::assertSame(255, $this->field($fields, 'title')->length);
        self::assertFalse($this->field($fields, 'title')->nullable);
        self::assertTrue($this->field($fields, 'content')->nullable);
        self::assertSame(10, $this->field($fields, 'price')->precision);
        self::assertSame(2, $this->field($fields, 'price')->scale);
        self::assertTrue($this->field($fields, 'price')->nullable);
        self::assertSame(Status::class, $this->field($fields, 'status')->enumType);
        self::assertTrue($this->field($fields, 'id')->id);
        self::assertFalse($this->field($fields, 'id')->nullable);
    }

    public function testConflictIsFlaggedButNeverResolved(): void
    {
        $price = $this->fields(Article::class)['price'];

        self::assertTrue($price->conflict);
        self::assertSame(StringFormatter::class, $price->formatter);
        self::assertSame('string', $price->phpType);
        self::assertSame('decimal', $price->doctrineType);
    }

    public function testCategoryFacts(): void
    {
        $fields = $this->fields(Category::class);

        self::assertSame(['integer', 'int', 'number'], $fields['id']->typeHierarchy);
        self::assertSame(IntlNumberFormatter::class, $fields['id']->formatter);
        self::assertFalse($fields['id']->conflict);

        self::assertSame(['string'], $fields['name']->typeHierarchy);
        self::assertSame(StringFormatter::class, $fields['name']->formatter);
        self::assertSame(255, $this->field($fields, 'name')->length);
    }

    /**
     * @param array<PropertyMetadata> $properties
     */
    private function field(array $properties, string $name): FieldMetadata
    {
        $property = $properties[$name];
        self::assertInstanceOf(FieldMetadata::class, $property, $name);

        return $property;
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
