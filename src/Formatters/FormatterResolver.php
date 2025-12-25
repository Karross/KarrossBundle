<?php

namespace Karross\Formatters;

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
    /**
     * @return class-string<ValueFormatterInterface>
     */
    public function resolve(PropertyType $type): string
    {
        return match ($type) {
            PropertyType::Boolean => BooleanFormatter::class,
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
}
