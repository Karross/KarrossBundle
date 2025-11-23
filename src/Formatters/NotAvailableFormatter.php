<?php

namespace Karross\Formatters;

class NotAvailableFormatter implements ValueFormatterInterface
{
    public static function format(mixed $value, ?Context $context = null)
    {
        return 'N/A';
    }
}
