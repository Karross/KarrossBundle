<?php

namespace Karross\Formatters;

class StringFormatter implements ValueFormatterInterface
{
    public static function format(mixed $value, ?FormattingContext $context = null): string
    {
        if (null === $value) {
            return '';
        }

        return (string) $value;
    }
}
