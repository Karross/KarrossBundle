<?php

namespace Karross\Formatters;

use Karross\Formatters\Boolean\TrueFalseFormatter;
use Karross\Formatters\DateTime\DateFormatter;
use Karross\Formatters\DateTime\DateTimeFormatter;
use Karross\Formatters\DateTime\TimeFormatter;
use Karross\Metadata\PropertyType;

/**
 * Resolves the appropriate formatter for a given property type.
 * Simple mapping: PropertyType → Formatter class.
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
    public function resolve(PropertyType $type): string
    {
        return match ($type) {
            PropertyType::Boolean => TrueFalseFormatter::class,
            PropertyType::Integer, PropertyType::Float => IntlNumberFormatter::class,
            PropertyType::String, PropertyType::Text => StringFormatter::class,
            PropertyType::Date => DateFormatter::class,
            PropertyType::Time => TimeFormatter::class,
            PropertyType::DateTime => DateTimeFormatter::class,
            PropertyType::Enum => EnumFormatter::class,
            PropertyType::Array,
            PropertyType::Single,
            PropertyType::Collection,
            PropertyType::Unknown => NotAvailableFormatter::class,
        };
    }

    public function get(string $formatter): ValueFormatterInterface
    {
        return $this->formatters[$formatter] ?? throw new \InvalidArgumentException(\sprintf('Unknown formatter "%s". Registered formatters: %s', $formatter, implode(', ', array_keys($this->formatters) ?: ['none'])));
    }
}
