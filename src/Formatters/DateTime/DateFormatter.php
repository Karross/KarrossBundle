<?php

namespace Karross\Formatters\DateTime;

use IntlDateFormatter;
use Karross\Formatters\FormattingContext;

class DateFormatter extends AbstractDateTimeFormatter
{
    protected static function resolvePattern(?FormattingContext $context): array
    {
        if ($context?->dateFormat !== null) {
            return [IntlDateFormatter::NONE, IntlDateFormatter::NONE, $context->dateFormat];
        }

        return [IntlDateFormatter::MEDIUM, IntlDateFormatter::NONE, null];
    }
}
