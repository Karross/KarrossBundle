<?php

namespace Karross\Formatters\DateTime;

use IntlDateFormatter;
use Karross\Formatters\FormattingContext;

class DateTimeFormatter extends AbstractDateTimeFormatter
{
    protected static function resolvePattern(?FormattingContext $context): array
    {
        // Priority: dateTimeFormat, then (dateFormat + timeFormat), then localized default
        if ($context?->dateTimeFormat !== null) {
            return [IntlDateFormatter::NONE, IntlDateFormatter::NONE, $context->dateTimeFormat];
        }

        if ($context?->dateFormat !== null && $context?->timeFormat !== null) {
            return [
                IntlDateFormatter::NONE,
                IntlDateFormatter::NONE,
                $context->dateFormat . ' ' . $context->timeFormat,
            ];
        }

        return [IntlDateFormatter::MEDIUM, IntlDateFormatter::SHORT, null];
    }
}
