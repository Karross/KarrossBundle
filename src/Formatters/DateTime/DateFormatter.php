<?php

namespace Karross\Formatters\DateTime;

use Karross\Formatters\FormattingContext;

class DateFormatter extends AbstractDateTimeFormatter
{
    protected static function resolveFormat(?FormattingContext $context): string
    {
        return $context?->dateFormat ?? 'L';
    }
}

