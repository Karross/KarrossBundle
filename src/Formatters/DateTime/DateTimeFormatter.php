<?php

namespace Karross\Formatters\DateTime;

use Karross\Formatters\FormattingContext;

class DateTimeFormatter extends AbstractDateTimeFormatter
{
    protected static function resolvePattern(?FormattingContext $context): array
    {
        // Priority: dateTimeFormat, then (dateFormat + timeFormat), then localized default
        if (null !== $context?->dateTimeFormat) {
            return [\IntlDateFormatter::NONE, \IntlDateFormatter::NONE, $context->dateTimeFormat];
        }

        if (null !== $context?->dateFormat && null !== $context?->timeFormat) {
            return [
                \IntlDateFormatter::NONE,
                \IntlDateFormatter::NONE,
                $context->dateFormat.' '.$context->timeFormat,
            ];
        }

        return [\IntlDateFormatter::MEDIUM, \IntlDateFormatter::SHORT, null];
    }
}
