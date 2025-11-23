<?php

namespace Karross\Formatters;

class NoFormatter implements ValueFormatterInterface
{
    public static function format(mixed $value, ?Context $context = null)
    {
        return $value;
    }
}
