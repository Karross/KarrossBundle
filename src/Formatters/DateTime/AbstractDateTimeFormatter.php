<?php

namespace Karross\Formatters\DateTime;

use IntlDateFormatter;
use Karross\Formatters\FormattingContext;
use Karross\Formatters\ValueFormatterInterface;

/**
 * Base formatter for absolute dates using PHP's native IntlDateFormatter (ICU).
 *
 * Each concrete subclass resolves how the date is rendered (date only, time
 * only, or both) and which ICU pattern/length to apply. The result is always
 * localized based on the FormattingContext locale.
 */
abstract class AbstractDateTimeFormatter implements ValueFormatterInterface
{
    public function format(mixed $value, ?FormattingContext $context = null): string
    {
        if (null === $value) {
            return '';
        }

        if (!$value instanceof \DateTimeInterface) {
            return 'N/A';
        }

        $locale = $context?->locale ?? FormattingContext::DEFAULT_LOCALE;

        [$dateType, $timeType, $pattern] = $this->resolvePattern($context);

        $formatter = null !== $pattern
            ? new \IntlDateFormatter($locale, \IntlDateFormatter::NONE, \IntlDateFormatter::NONE, null, null, $pattern)
            : new \IntlDateFormatter($locale, $dateType, $timeType);

        if (($timezone = $context?->timezone) !== null) {
            $formatter->setTimeZone($timezone);
        }

        return $formatter->format($value) ?: 'N/A';
    }

    /**
     * Resolve the ICU datePart/timePart constants and optional custom pattern.
     *
     * @return array{0:int, 1:int, 2:?string} [dateType, timeType, pattern]
     */
    abstract protected function resolvePattern(?FormattingContext $context): array;
}
