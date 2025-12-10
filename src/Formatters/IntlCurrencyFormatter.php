<?php

namespace Karross\Formatters;

use CommerceGuys\Intl\Currency\CurrencyRepository;
use CommerceGuys\Intl\Formatter\CurrencyFormatter as IntlCurrencyFormatterLib;
use CommerceGuys\Intl\NumberFormat\NumberFormatRepository;

class IntlCurrencyFormatter implements ValueFormatterInterface
{
    public static function format(mixed $value, ?FormattingContext $context = null): string
    {
        if ($value === null) {
            return '';
        }

        $locale = $context?->locale ?? FormattingContext::DEFAULT_LOCALE;
        $currencyCode = 'EUR'; // Default currency, could be passed via context
        
        $numberFormatRepository = new NumberFormatRepository();
        $currencyRepository = new CurrencyRepository();
        $formatter = new IntlCurrencyFormatterLib($numberFormatRepository, $currencyRepository, ['locale' => $locale]);

        return $formatter->format((string) $value, $currencyCode);
    }
}
