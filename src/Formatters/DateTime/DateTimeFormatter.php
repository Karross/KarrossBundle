<?php

namespace Karross\Formatters\DateTime;

use Karross\Formatters\FormattingContext;

class DateTimeFormatter extends AbstractDateTimeFormatter
{
    protected static function resolveFormat(?FormattingContext $context): string
    {
        // Priority: dateTimeFormat > dateFormat + timeFormat > default
        return $context?->dateTimeFormat
            ?? ($context?->dateFormat && $context?->timeFormat
                ? $context->dateFormat . ' ' . $context->timeFormat
                : 'L LT');
    }
}


