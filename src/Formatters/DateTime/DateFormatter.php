<?php

namespace Karross\Formatters\DateTime;

use Karross\Formatters\FormattingContext;

class DateFormatter extends AbstractDateTimeFormatter
{
    protected static function resolvePattern(?FormattingContext $context): array
    {
        if (null !== $context?->dateFormat) {
            return [\IntlDateFormatter::NONE, \IntlDateFormatter::NONE, $context->dateFormat];
        }

        return [\IntlDateFormatter::MEDIUM, \IntlDateFormatter::NONE, null];
    }
}
