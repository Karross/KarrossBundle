<?php

namespace Tests\Unit\Formatters;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\FieldMapping;
use Karross\Formatters\Boolean\TrueFalseFormatter;
use Karross\Formatters\EnumFormatter;
use Karross\Formatters\FormatterResolver;
use Karross\Formatters\IntlNumberFormatter;
use Karross\Formatters\Resolvers\BooleanFormatterResolver;
use Karross\Formatters\Resolvers\FloatFormatterResolver;
use Karross\Formatters\Resolvers\IntegerFormatterResolver;
use Karross\Formatters\StringFormatter;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use TestedApp\Entity\Status;

/**
 * Exhaustive reference of what the resolver chain (Boolean → Integer → Float)
 * returns for every phpType × doctrineType pair.
 *
 * No kernel, no container — pure combinatorial logic.
 */
final class FormatterResolverTest extends TestCase
{
    private FormatterResolver $resolver;

    protected function setUp(): void
    {
        $this->resolver = new FormatterResolver(
            [], // formatters not needed — resolve() returns class-strings, only get() uses instances
            [new BooleanFormatterResolver(), new IntegerFormatterResolver(), new FloatFormatterResolver()],
        );
    }

    #[DataProvider('resolverCases')]
    public function testResolverChain(?string $phpType, ?FieldMapping $mapping, string $expected): void
    {
        self::assertSame($expected, $this->resolver->resolve($phpType, $mapping));
    }

    /**
     * Exhaustive phpType × doctrineType matrix for the 3 specific resolvers.
     *
     * @return iterable<string, array{string|null, FieldMapping|null, class-string}>
     */
    public static function resolverCases(): iterable
    {
        // ── Boolean resolver: phpType 'bool' → TrueFalseFormatter ───
        yield 'bool / string' => ['bool', self::mapping(Types::STRING), TrueFalseFormatter::class];
        yield 'bool / text' => ['bool', self::mapping(Types::TEXT), TrueFalseFormatter::class];
        yield 'bool / integer' => ['bool', self::mapping(Types::INTEGER), TrueFalseFormatter::class];
        yield 'bool / boolean' => ['bool', self::mapping(Types::BOOLEAN), TrueFalseFormatter::class];
        yield 'bool / decimal' => ['bool', self::mapping(Types::DECIMAL), TrueFalseFormatter::class];
        yield 'bool / float' => ['bool', self::mapping(Types::FLOAT), TrueFalseFormatter::class];
        yield 'bool / datetime' => ['bool', self::mapping(Types::DATETIME_MUTABLE), TrueFalseFormatter::class];
        yield 'bool / json' => ['bool', self::mapping(Types::JSON), TrueFalseFormatter::class];
        yield 'bool / null mapping' => ['bool', null, TrueFalseFormatter::class];

        // ── Boolean resolver: doctrineType 'boolean' → TrueFalseFormatter ──
        yield 'null / boolean' => [null, self::mapping(Types::BOOLEAN), TrueFalseFormatter::class];
        yield 'string / boolean' => ['string', self::mapping(Types::BOOLEAN), TrueFalseFormatter::class];
        yield 'int / boolean' => ['int', self::mapping(Types::BOOLEAN), TrueFalseFormatter::class];
        yield 'float / boolean' => ['float', self::mapping(Types::BOOLEAN), TrueFalseFormatter::class];

        // ── Integer resolver: phpType 'int' → IntlNumberFormatter ───
        yield 'int / string' => ['int', self::mapping(Types::STRING), IntlNumberFormatter::class];
        yield 'int / text' => ['int', self::mapping(Types::TEXT), IntlNumberFormatter::class];
        yield 'int / integer' => ['int', self::mapping(Types::INTEGER), IntlNumberFormatter::class];
        yield 'int / smallint' => ['int', self::mapping(Types::SMALLINT), IntlNumberFormatter::class];
        yield 'int / bigint' => ['int', self::mapping(Types::BIGINT), IntlNumberFormatter::class];
        yield 'int / decimal' => ['int', self::mapping(Types::DECIMAL), IntlNumberFormatter::class];
        yield 'int / float' => ['int', self::mapping(Types::FLOAT), IntlNumberFormatter::class];
        yield 'int / datetime' => ['int', self::mapping(Types::DATETIME_MUTABLE), IntlNumberFormatter::class];
        yield 'int / null mapping' => ['int', null, IntlNumberFormatter::class];

        // ── Integer resolver: doctrineType integer family → IntlNumberFormatter ──
        yield 'null / integer' => [null, self::mapping(Types::INTEGER), IntlNumberFormatter::class];
        yield 'string / integer' => ['string', self::mapping(Types::INTEGER), IntlNumberFormatter::class];
        yield 'null / smallint' => [null, self::mapping(Types::SMALLINT), IntlNumberFormatter::class];
        yield 'string / smallint' => ['string', self::mapping(Types::SMALLINT), IntlNumberFormatter::class];
        yield 'null / bigint' => [null, self::mapping(Types::BIGINT), IntlNumberFormatter::class];
        yield 'string / bigint' => ['string', self::mapping(Types::BIGINT), IntlNumberFormatter::class];

        // ── Float resolver: phpType 'float' → IntlNumberFormatter ───
        yield 'float / string' => ['float', self::mapping(Types::STRING), IntlNumberFormatter::class];
        yield 'float / text' => ['float', self::mapping(Types::TEXT), IntlNumberFormatter::class];
        yield 'float / integer' => ['float', self::mapping(Types::INTEGER), IntlNumberFormatter::class];
        yield 'float / decimal' => ['float', self::mapping(Types::DECIMAL), IntlNumberFormatter::class];
        yield 'float / float' => ['float', self::mapping(Types::FLOAT), IntlNumberFormatter::class];
        yield 'float / datetime' => ['float', self::mapping(Types::DATETIME_MUTABLE), IntlNumberFormatter::class];
        yield 'float / null mapping' => ['float', null, IntlNumberFormatter::class];

        // ── Float resolver: doctrineType decimal/float → IntlNumberFormatter ──
        yield 'null / decimal' => [null, self::mapping(Types::DECIMAL), IntlNumberFormatter::class];
        yield 'string / decimal' => ['string', self::mapping(Types::DECIMAL), IntlNumberFormatter::class];
        yield 'null / float' => [null, self::mapping(Types::FLOAT), IntlNumberFormatter::class];
        yield 'string / float' => ['string', self::mapping(Types::FLOAT), IntlNumberFormatter::class];

        // ── enumType rejection — all 3 resolvers refuse → fallback ──
        // Note: phpType 'string' hits StringFormatter before enumType is checked in the fallback.
        yield 'bool / boolean + enumType' => ['bool', self::mapping(Types::BOOLEAN, Status::class), EnumFormatter::class];
        yield 'int / integer + enumType' => ['int', self::mapping(Types::INTEGER, Status::class), EnumFormatter::class];
        yield 'float / decimal + enumType' => ['float', self::mapping(Types::DECIMAL, Status::class), EnumFormatter::class];
        yield 'null / boolean + enumType' => [null, self::mapping(Types::BOOLEAN, Status::class), EnumFormatter::class];
        yield 'null / integer + enumType' => [null, self::mapping(Types::INTEGER, Status::class), EnumFormatter::class];
        yield 'null / smallint + enumType' => [null, self::mapping(Types::SMALLINT, Status::class), EnumFormatter::class];
        yield 'string / boolean + enumType → enumType wins' => ['string', self::mapping(Types::BOOLEAN, Status::class), EnumFormatter::class];
        yield 'string / decimal + enumType → enumType wins' => ['string', self::mapping(Types::DECIMAL, Status::class), EnumFormatter::class];
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
