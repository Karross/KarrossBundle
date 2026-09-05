<?php

namespace Karross\Formatters;

use CommerceGuys\Intl\Currency\CurrencyRepository;
use CommerceGuys\Intl\Formatter\CurrencyFormatter as IntlCurrencyFormatterLib;
use CommerceGuys\Intl\NumberFormat\NumberFormatRepository;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('karross.formatter')]
class IntlCurrencyFormatter implements ValueFormatterInterface
{
    public function __construct(
        private NumberFormatRepository $numberFormatRepository,
        private CurrencyRepository $currencyRepository,
    ) {
    }

    public function format(mixed $value, ?FormattingContext $context = null): string
    {
        if (null === $value) {
            return '';
        }

        $locale = $context?->locale ?? FormattingContext::DEFAULT_LOCALE;
        $currencyCode = 'EUR'; // Default currency, could be passed via context

        $formatter = new IntlCurrencyFormatterLib($this->numberFormatRepository, $this->currencyRepository, ['locale' => $locale]);

        return $formatter->format((string) $value, $currencyCode);
    }
}
