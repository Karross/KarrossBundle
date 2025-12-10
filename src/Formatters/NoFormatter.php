<?php

namespace Karross\Formatters;

class NoFormatter implements ValueFormatterInterface
{
    public static function format(mixed $value, ?FormattingContext $context = null)
    {
        return $value;
    }
}
