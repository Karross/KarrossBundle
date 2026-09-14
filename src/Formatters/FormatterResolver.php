<?php

namespace Karross\Formatters;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\FieldMapping;
use Karross\Formatters\Boolean\TrueFalseFormatter;
use Karross\Formatters\DateTime\DateFormatter;
use Karross\Formatters\DateTime\DateTimeFormatter;
use Karross\Formatters\DateTime\TimeFormatter;

/**
 * Resolves the formatter for a property from its gathered facts.
 * Deterministic chain: the PHP type wins when it exists, except the datetime
 * family which is refined by the Doctrine type (date/time/datetime). Conflicting
 * pairs are only flagged by the metadata — the case tickets decide which value
 * wins, never this resolver.
 */
final class FormatterResolver
{
    /** @var array<class-string<ValueFormatterInterface>, ValueFormatterInterface> */
    private array $formatters = [];

    /**
     * @param iterable<ValueFormatterInterface> $formatters
     */
    public function __construct(iterable $formatters)
    {
        foreach ($formatters as $formatter) {
            $this->formatters[$formatter::class] = $formatter;
        }
    }

    /**
     * @return class-string<ValueFormatterInterface>
     */
    public function resolve(?string $phpType, ?FieldMapping $fieldMapping = null): string
    {
        $doctrineType = $fieldMapping?->type;
        $enumType = $fieldMapping?->enumType;

        if (null !== $phpType && $this->isDatetime($phpType)) {
            return $this->resolveDatetime($doctrineType);
        }

        if (null !== $phpType) {
            return match ($phpType) {
                'bool' => TrueFalseFormatter::class,
                'int', 'float' => IntlNumberFormatter::class,
                'string' => StringFormatter::class,
                'array' => NotAvailableFormatter::class,
                default => $this->resolvePhpClass($phpType, $doctrineType, $enumType),
            };
        }

        return $this->resolveDoctrineType($doctrineType, $enumType);
    }

    public function get(string $formatter): ValueFormatterInterface
    {
        return $this->formatters[$formatter] ?? throw new \InvalidArgumentException(\sprintf('Unknown formatter "%s". Registered formatters: %s', $formatter, implode(', ', array_keys($this->formatters) ?: ['none'])));
    }

    /**
     * @return class-string<ValueFormatterInterface>
     */
    private function resolvePhpClass(string $className, ?string $doctrineType, ?string $enumType): string
    {
        if (is_a($className, \UnitEnum::class, true)) {
            return EnumFormatter::class;
        }

        if (method_exists($className, '__toString')) {
            return StringFormatter::class;
        }

        return $this->resolveDoctrineType($doctrineType, $enumType);
    }

    /**
     * @return class-string<ValueFormatterInterface>
     */
    private function resolveDoctrineType(?string $doctrineType, ?string $enumType): string
    {
        if (null !== $enumType) {
            return EnumFormatter::class;
        }

        return match ($doctrineType) {
            Types::BOOLEAN => TrueFalseFormatter::class,
            Types::SMALLINT, Types::INTEGER, Types::BIGINT, Types::DECIMAL, Types::FLOAT => IntlNumberFormatter::class,
            Types::STRING, Types::ASCII_STRING, Types::GUID, Types::TEXT => StringFormatter::class,
            Types::DATE_MUTABLE, Types::DATE_IMMUTABLE => DateFormatter::class,
            Types::TIME_MUTABLE, Types::TIME_IMMUTABLE => TimeFormatter::class,
            Types::DATETIME_MUTABLE, Types::DATETIME_IMMUTABLE,
            Types::DATETIMETZ_MUTABLE, Types::DATETIMETZ_IMMUTABLE => DateTimeFormatter::class,
            default => NotAvailableFormatter::class,
        };
    }

    /**
     * @return class-string<ValueFormatterInterface>
     */
    private function resolveDatetime(?string $doctrineType): string
    {
        return match ($doctrineType) {
            Types::DATE_MUTABLE, Types::DATE_IMMUTABLE => DateFormatter::class,
            Types::TIME_MUTABLE, Types::TIME_IMMUTABLE => TimeFormatter::class,
            default => DateTimeFormatter::class,
        };
    }

    private function isDatetime(string $phpType): bool
    {
        return \in_array($phpType, [\DateTime::class, \DateTimeImmutable::class, \DateTimeInterface::class], true);
    }
}
