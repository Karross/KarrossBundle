<?php

namespace Karross\Formatters\DateTime;

use Karross\Formatters\FormattingContext;

class TimeFormatter extends AbstractDateTimeFormatter
{
    protected static function resolveFormat(?FormattingContext $context): string
    {
        return $context?->timeFormat ?? 'LT';
    }
}

