<?php

namespace Karross\Formatters;

class EnumFormatter implements ValueFormatterInterface
{
    public static function format(mixed $value, ?FormattingContext $context = null): string
    {
        if (null === $value) {
            return '';
        }

        if (!$value instanceof \UnitEnum) {
            return 'N/A';
        }

        // For BackedEnum, return the value, otherwise return the name
        if ($value instanceof \BackedEnum) {
            return (string) $value->value;
        }

        return $value->name;
    }
}
