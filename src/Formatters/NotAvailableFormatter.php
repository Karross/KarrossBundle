<?php

namespace Karross\Formatters;

class NotAvailableFormatter implements ValueFormatterInterface
{
    public static function format(mixed $value, ?FormattingContext $context = null)
    {
        return 'N/A';
    }
}
