<?php

namespace Karross\Formatters;

use DateTimeInterface;
use IntlDateFormatter;

class TimeFormatter implements ValueFormatterInterface
{
    public static function format(mixed $value, ?FormattingContext $context = null): string
    {
        if ($value === null) {
            return '';
        }

        if (!$value instanceof DateTimeInterface) {
            return 'N/A';
        }

        $locale = $context?->locale ?? FormattingContext::DEFAULT_LOCALE;
        
        $formatter = new IntlDateFormatter(
            $locale,
            IntlDateFormatter::NONE,
            IntlDateFormatter::SHORT
        );

        return $formatter->format($value) ?: 'N/A';
    }
}
