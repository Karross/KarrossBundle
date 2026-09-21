<?php

namespace Integration\Formatters;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\FieldMapping;
use Karross\Formatters\Boolean\TrueFalseFormatter;
use Karross\Formatters\EnumFormatter;
use Karross\Formatters\FormatterResolver;
use Karross\Formatters\IntlNumberFormatter;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Container;
use TestedApp\Entity\Status;
use TestedApp\Kernel;

final class FormatterResolverTest extends TestCase
{
    private FormatterResolver $resolver;

    protected function setUp(): void
    {
        $kernel = new Kernel('test_formatter_resolver', true, [
            __DIR__.'/../TestedApp/config/doctrine_standard.php',
        ]);
        $kernel->boot();

        $container = $kernel->getContainer()->get('test.service_container');
        self::assertInstanceOf(Container::class, $container);

        $resolver = $container->get(FormatterResolver::class);
        self::assertInstanceOf(FormatterResolver::class, $resolver);
        $this->resolver = $resolver;
    }

    #[DataProvider('chainCases')]
    public function testChainResolvesEachPairToItsFamilyFormatter(?string $phpType, ?FieldMapping $fieldMapping, string $expected): void
    {
        self::assertSame($expected, $this->resolver->resolve($phpType, $fieldMapping));
    }

    /**
     * @return iterable<string, array{string|null, FieldMapping|null, class-string}>
     */
    public static function chainCases(): iterable
    {
        yield 'string on integer becomes a number' => ['string', self::mapping(Types::INTEGER), IntlNumberFormatter::class];
        yield 'string on decimal becomes a number, Float comes after Integer' => ['string', self::mapping(Types::DECIMAL), IntlNumberFormatter::class];
        yield 'no type on integer' => [null, self::mapping(Types::INTEGER), IntlNumberFormatter::class];
        yield 'no type on decimal' => [null, self::mapping(Types::DECIMAL), IntlNumberFormatter::class];
        yield 'float on decimal' => ['float', self::mapping(Types::DECIMAL), IntlNumberFormatter::class];
        yield 'float on float' => ['float', self::mapping(Types::FLOAT), IntlNumberFormatter::class];
        yield 'no type on smallint with enumType describes an enum' => [null, self::mapping(Types::SMALLINT, Status::class), EnumFormatter::class];
        yield 'int on integer' => ['int', self::mapping(Types::INTEGER), IntlNumberFormatter::class];
        yield 'int on boolean stays boolean, Boolean comes first' => ['int', self::mapping(Types::BOOLEAN), TrueFalseFormatter::class];
        yield 'bool on boolean' => ['bool', self::mapping(Types::BOOLEAN), TrueFalseFormatter::class];
        yield 'no type on boolean with enumType describes an enum' => [null, self::mapping(Types::BOOLEAN, Status::class), EnumFormatter::class];
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
