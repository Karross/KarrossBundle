<?php

namespace Karross\Formatters\DateTime;

use Karross\Formatters\FormattingContext;

class TimeFormatter extends AbstractDateTimeFormatter
{
    protected static function resolvePattern(?FormattingContext $context): array
    {
        if (null !== $context?->timeFormat) {
            return [\IntlDateFormatter::NONE, \IntlDateFormatter::NONE, $context->timeFormat];
        }

        return [\IntlDateFormatter::NONE, \IntlDateFormatter::SHORT, null];
    }
}
