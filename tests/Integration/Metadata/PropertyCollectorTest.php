<?php

namespace Integration\Metadata;

use Doctrine\ORM\Mapping\FieldMapping;
use Karross\Metadata\Collect\PropertyCollector;
use Karross\Metadata\Computed\Cardinality;
use PHPUnit\Framework\TestCase;
use TestedApp\Entity\Status;

final class PropertyCollectorTest extends TestCase
{
    public function testTypeHierarchyIsSpecificToGeneric(): void
    {
        self::assertSame(['bigint', 'int', 'number'], PropertyCollector::buildTypeHierarchy('int', self::mapping('bigint')));
        self::assertSame(['blob', 'string'], PropertyCollector::buildTypeHierarchy('string', self::mapping('blob')));
        self::assertSame(['string'], PropertyCollector::buildTypeHierarchy('string', self::mapping('string')));
        self::assertSame(['integer', 'int', 'number'], PropertyCollector::buildTypeHierarchy('int', self::mapping('integer')));
        self::assertSame(['decimal', 'string'], PropertyCollector::buildTypeHierarchy('string', self::mapping('decimal')));
        self::assertSame(['json', 'array'], PropertyCollector::buildTypeHierarchy('array', self::mapping('json')));
        self::assertSame(['integer'], PropertyCollector::buildTypeHierarchy(null, self::mapping('integer')));
        self::assertSame(['int', 'number'], PropertyCollector::buildTypeHierarchy('int', null));
    }

    public function testTypeHierarchyResolvesToSemanticKeys(): void
    {
        self::assertSame(
            ['datetime'],
            PropertyCollector::buildTypeHierarchy(\DateTimeImmutable::class, self::mapping('datetime_immutable')),
        );
        self::assertSame(
            ['date', 'datetime'],
            PropertyCollector::buildTypeHierarchy(\DateTimeImmutable::class, self::mapping('date_immutable')),
        );
        self::assertSame(
            ['time', 'datetime'],
            PropertyCollector::buildTypeHierarchy(\DateTimeImmutable::class, self::mapping('time_immutable')),
        );
        self::assertSame(
            ['datetime'],
            PropertyCollector::buildTypeHierarchy(null, self::mapping('datetimetz')),
        );
    }

    public function testTypeHierarchySettlesEnumsByEnumType(): void
    {
        $enumType = Status::class;

        self::assertSame(['enum', 'string'], PropertyCollector::buildTypeHierarchy(Status::class, self::mapping('string', $enumType)));
        self::assertSame(['enum', 'string'], PropertyCollector::buildTypeHierarchy(Status::class, self::mapping('string')));
        self::assertSame(
            ['enum', 'string'],
            PropertyCollector::buildTypeHierarchy('string', self::mapping('string', $enumType)),
        );
    }

    public function testAssociationTypeHierarchyFollowsCardinality(): void
    {
        self::assertSame(['one'], PropertyCollector::associationTypeHierarchy(Cardinality::TO_ONE));
        self::assertSame(['many'], PropertyCollector::associationTypeHierarchy(Cardinality::TO_MANY));
    }

    public function testConflictRules(): void
    {
        self::assertTrue(PropertyCollector::isConflict('string', self::mapping('json')));
        self::assertTrue(PropertyCollector::isConflict('string', self::mapping('decimal')));
        self::assertFalse(PropertyCollector::isConflict('string', self::mapping('string')));
        self::assertFalse(PropertyCollector::isConflict('bool', self::mapping('boolean')));
        self::assertFalse(PropertyCollector::isConflict('int', self::mapping('bigint')));
        self::assertFalse(PropertyCollector::isConflict('array', self::mapping('json')));
        self::assertFalse(PropertyCollector::isConflict(\DateTimeImmutable::class, self::mapping('date_immutable')));
        self::assertFalse(PropertyCollector::isConflict(null, self::mapping('json')));
    }

    public function testEnumTypeSettlesTheEnumPair(): void
    {
        self::assertFalse(PropertyCollector::isConflict(Status::class, self::mapping('string', Status::class)));
    }

    public function testUnknownDoctrineTypeIsFlaggedAsNonStabilized(): void
    {
        self::assertTrue(PropertyCollector::isConflict('string', self::mapping('my_custom_type')));
    }

    /**
     * @param class-string<\BackedEnum>|null $enumType
     */
    private static function mapping(string $type, ?string $enumType = null): FieldMapping
    {
        $mapping = new FieldMapping($type, 'field', 'field');

        if (null !== $enumType) {
            $mapping->enumType = $enumType;
        }

        return $mapping;
    }
}
