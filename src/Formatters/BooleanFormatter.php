<?php

namespace Karross\Formatters;

class BooleanFormatter implements ValueFormatterInterface
{
    public static function format(mixed $value, ?Context $context = null)
    {
        return $value ? 'true' : 'false';
    }
}
