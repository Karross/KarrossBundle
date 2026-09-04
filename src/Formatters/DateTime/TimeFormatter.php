<?php

namespace Karross\Formatters\DateTime;

use IntlDateFormatter;
use Karross\Formatters\FormattingContext;

class TimeFormatter extends AbstractDateTimeFormatter
{
    protected static function resolvePattern(?FormattingContext $context): array
    {
        if ($context?->timeFormat !== null) {
            return [IntlDateFormatter::NONE, IntlDateFormatter::NONE, $context->timeFormat];
        }

        return [IntlDateFormatter::NONE, IntlDateFormatter::SHORT, null];
    }
}
