<?php

namespace Karross\Formatters;

use CommerceGuys\Intl\Formatter\NumberFormatter as IntlNumberFormatterLib;
use CommerceGuys\Intl\NumberFormat\NumberFormatRepository;

class IntlNumberFormatter implements ValueFormatterInterface
{
    public static function format(mixed $value, ?FormattingContext $context = null): string
    {
        if ($value === null) {
            return '';
        }

        $locale = $context?->locale ?? FormattingContext::DEFAULT_LOCALE;
        
        $numberFormatRepository = new NumberFormatRepository();
        $formatter = new IntlNumberFormatterLib($numberFormatRepository, ['locale' => $locale]);

        return $formatter->format((string) $value);
    }
}
