<?php

namespace Karross\Metadata\Collect;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\FieldMapping;
use Karross\Metadata\Computed\Cardinality;

/**
 * Pure static readings and deductions over a property's raw sources (the PHP
 * reflection and the Doctrine mapping). It holds no state and produces no
 * projection: the read-model assembly is carried out during the build, from
 * these readings.
 */
final class PropertyCollector
{
    private const PHP_PRIMITIVES = ['bool', 'int', 'float', 'string', 'array'];

    public static function resolvePhpType(?\ReflectionProperty $property): ?string
    {
        if (null === $property) {
            return null;
        }

        $type = $property->getType();

        if ($type instanceof \ReflectionNamedType) {
            return $type->getName();
        }

        if ($type instanceof \ReflectionUnionType) {
            foreach ($type->getTypes() as $member) {
                if ($member instanceof \ReflectionNamedType && 'null' !== $member->getName()) {
                    return $member->getName();
                }
            }
        }

        return null;
    }

    /**
     * A pair is in conflict when the two sources do not belong to the same
     * raw data family (granted that a bare PHP type is authoritative when it
     * exists, and that the enumType fact settles the enum mapping). The flag
     * only signals the pair: it never resolves it.
     */
    public static function isConflict(?string $phpType, ?FieldMapping $fieldMapping = null): bool
    {
        $doctrineType = $fieldMapping?->type;

        if (null === $phpType || null === $doctrineType) {
            return false;
        }

        if (null !== $fieldMapping->enumType) {
            return false;
        }

        $phpFamily = self::phpFamily($phpType);
        $doctrineFamily = self::doctrineFamily($doctrineType);

        if (null === $phpFamily || null === $doctrineFamily) {
            return true;
        }

        return match ($phpFamily) {
            'number' => !\in_array($doctrineFamily, ['number-int', 'number-decimal'], true),
            'text', 'stringable' => !\in_array($doctrineFamily, ['text', 'text-long'], true),
            'bool' => 'bool' !== $doctrineFamily,
            'arrayish' => 'arrayish' !== $doctrineFamily,
            'datetime' => 'datetime' !== $doctrineFamily,
            'enum' => !\in_array($doctrineFamily, ['text', 'text-long'], true),
            default => true,
        };
    }

    /**
     * Ordered chain of type names, from the most specific to the most generic,
     * action-independent. It feeds template candidate composition, never the
     * formatter. The chain is contractual: date and time columns resolve to a
     * semantic key ('date' / 'time') then to the 'datetime' umbrella (the raw
     * Doctrine variant — mutable or immutable — is intentionally dropped), and
     * enum columns resolve to ['enum', 'string'].
     *
     * @return list<string>
     */
    public static function buildTypeHierarchy(?string $phpType, ?FieldMapping $fieldMapping = null): array
    {
        $doctrineType = $fieldMapping?->type;
        $enumType = $fieldMapping?->enumType;

        if (null !== $enumType || 'enum' === self::phpFamily($phpType ?? '')) {
            return ['enum', 'string'];
        }

        $hierarchy = [];

        if (null !== $doctrineType) {
            [$specific, $umbrella] = self::datetimeChain($doctrineType);
            $hierarchy[] = $specific ?? $doctrineType;

            if (null !== $umbrella) {
                $hierarchy[] = $umbrella;
            }
        }

        if (null !== $phpType && \in_array($phpType, self::PHP_PRIMITIVES, true)) {
            $hierarchy[] = $phpType;
        }

        if ('number' === self::phpFamily($phpType ?? '')) {
            $hierarchy[] = 'number';
        }

        return array_values(array_unique($hierarchy));
    }

    /**
     * Semantic key of an association for template candidate composition:
     * 'one' for to-one associations, 'many' for to-many (collection) ones.
     *
     * @return list<string>
     */
    public static function associationTypeHierarchy(Cardinality $cardinality): array
    {
        return [Cardinality::TO_MANY === $cardinality ? 'many' : 'one'];
    }

    /**
     * Resolves a Doctrine type to its semantic key and the generic 'datetime'
     * umbrella entry above it. Returns two nulls when the type is not a
     * date/time column.
     *
     * @return array{0: string|null, 1: string|null}
     */
    private static function datetimeChain(string $doctrineType): array
    {
        return match ($doctrineType) {
            Types::DATE_MUTABLE, Types::DATE_IMMUTABLE => ['date', 'datetime'],
            Types::TIME_MUTABLE, Types::TIME_IMMUTABLE => ['time', 'datetime'],
            Types::DATETIME_MUTABLE, Types::DATETIME_IMMUTABLE,
            Types::DATETIMETZ_MUTABLE, Types::DATETIMETZ_IMMUTABLE => ['datetime', null],
            default => [null, null],
        };
    }

    private static function phpFamily(string $phpType): ?string
    {
        return match ($phpType) {
            'bool' => 'bool',
            'int', 'float' => 'number',
            'string' => 'text',
            'array' => 'arrayish',
            \DateTime::class, \DateTimeImmutable::class, \DateTimeInterface::class => 'datetime',
            default => self::classFamily($phpType),
        };
    }

    private static function classFamily(string $className): ?string
    {
        if (is_a($className, \UnitEnum::class, true)) {
            return 'enum';
        }

        if (method_exists($className, '__toString')) {
            return 'stringable';
        }

        return null;
    }

    private static function doctrineFamily(string $doctrineType): ?string
    {
        return match ($doctrineType) {
            Types::BOOLEAN => 'bool',
            Types::SMALLINT, Types::INTEGER, Types::BIGINT => 'number-int',
            Types::DECIMAL, Types::FLOAT => 'number-decimal',
            Types::STRING, Types::ASCII_STRING, Types::GUID => 'text',
            Types::TEXT, Types::BLOB => 'text-long',
            Types::JSON => 'arrayish',
            Types::DATE_MUTABLE, Types::DATE_IMMUTABLE,
            Types::TIME_MUTABLE, Types::TIME_IMMUTABLE,
            Types::DATETIME_MUTABLE, Types::DATETIME_IMMUTABLE,
            Types::DATETIMETZ_MUTABLE, Types::DATETIMETZ_IMMUTABLE => 'datetime',
            default => null,
        };
    }
}
