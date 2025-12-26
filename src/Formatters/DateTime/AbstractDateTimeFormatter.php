<?php

namespace Karross\Formatters\DateTime;

use Carbon\Carbon;
use DateTimeInterface;
use Karross\Formatters\FormattingContext;
use Karross\Formatters\ValueFormatterInterface;

abstract class AbstractDateTimeFormatter implements ValueFormatterInterface
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
        $format = static::resolveFormat($context);

        return Carbon::instance($value)
            ->locale($locale)
            ->isoFormat($format);
    }

    abstract protected static function resolveFormat(?FormattingContext $context): string;
}

