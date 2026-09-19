<?php

namespace Integration\Formatters;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\FieldMapping;
use Karross\Formatters\IntlNumberFormatter;
use Karross\Formatters\Resolvers\IntegerFormatterResolver;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use TestedApp\Entity\Status;

final class IntegerFormatterResolverTest extends TestCase
{
    private IntegerFormatterResolver $resolver;

    protected function setUp(): void
    {
        $this->resolver = new IntegerFormatterResolver();
    }

    #[DataProvider('acceptedCases')]
    public function testAcceptsIntegerProperties(?string $phpType, ?FieldMapping $fieldMapping): void
    {
        self::assertTrue($this->resolver->accept($phpType, $fieldMapping));
        self::assertSame(IntlNumberFormatter::class, $this->resolver->resolve($phpType, $fieldMapping));
    }

    /**
     * @return iterable<string, array{string|null, FieldMapping|null}>
     */
    public static function acceptedCases(): iterable
    {
        yield 'int without mapping' => ['int', null];
        yield 'int on integer' => ['int', self::mapping(Types::INTEGER)];
        yield 'int on boolean also matches, the chain order decides' => ['int', self::mapping(Types::BOOLEAN)];
        yield 'string on smallint' => ['string', self::mapping(Types::SMALLINT)];
        yield 'string on integer' => ['string', self::mapping(Types::INTEGER)];
        yield 'string on bigint' => ['string', self::mapping(Types::BIGINT)];
        yield 'no type on smallint' => [null, self::mapping(Types::SMALLINT)];
        yield 'no type on integer' => [null, self::mapping(Types::INTEGER)];
        yield 'no type on bigint' => [null, self::mapping(Types::BIGINT)];
    }

    #[DataProvider('refusedCases')]
    public function testRefusesOtherFamilies(?string $phpType, ?FieldMapping $fieldMapping): void
    {
        self::assertFalse($this->resolver->accept($phpType, $fieldMapping));
    }

    /**
     * @return iterable<string, array{string|null, FieldMapping|null}>
     */
    public static function refusedCases(): iterable
    {
        yield 'no type at all' => [null, null];
        yield 'bool on integer stays boolean' => ['bool', self::mapping(Types::INTEGER)];
        yield 'float on integer follows the Float ticket' => ['float', self::mapping(Types::INTEGER)];
        yield 'string on decimal stays a string' => ['string', self::mapping(Types::DECIMAL)];
        yield 'no type on decimal' => [null, self::mapping(Types::DECIMAL)];
        yield 'no type on string' => [null, self::mapping(Types::STRING)];
        yield 'string on integer with enumType describes an enum' => ['string', self::mapping(Types::INTEGER, Status::class)];
        yield 'no type on smallint with enumType describes an enum' => [null, self::mapping(Types::SMALLINT, Status::class)];
        yield 'int with enumType describes an enum' => ['int', self::mapping(Types::INTEGER, Status::class)];
    }

    /**
     * @param class-string<\BackedEnum>|null $enumType
     */
    private static function mapping(string $type, ?string $enumType = null): FieldMapping
    {
        $mapping = FieldMapping::fromMappingArray([
            'fieldName' => 'property',
            'columnName' => 'property',
            'type' => $type,
        ]);

        if (null !== $enumType) {
            $mapping->enumType = $enumType;
        }

        return $mapping;
    }
}
