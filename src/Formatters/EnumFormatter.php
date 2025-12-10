<?php

namespace Karross\Formatters;

use UnitEnum;

class EnumFormatter implements ValueFormatterInterface
{
    public static function format(mixed $value, ?FormattingContext $context = null): string
    {
        if ($value === null) {
            return '';
        }

        if (!$value instanceof UnitEnum) {
            return 'N/A';
        }

        // For BackedEnum, return the value, otherwise return the name
        if ($value instanceof \BackedEnum) {
            return (string) $value->value;
        }

        return $value->name;
    }
}
