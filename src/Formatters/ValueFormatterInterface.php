<?php

namespace Karross\Formatters;

interface ValueFormatterInterface
{
    public static function format(mixed $value, ?FormattingContext $context = null);
}
