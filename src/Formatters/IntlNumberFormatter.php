<?php

namespace Karross\Formatters;

use CommerceGuys\Intl\Formatter\NumberFormatter as IntlNumberFormatterLib;
use CommerceGuys\Intl\NumberFormat\NumberFormatRepository;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('karross.formatter')]
class IntlNumberFormatter implements ValueFormatterInterface
{
    public function __construct(private NumberFormatRepository $numberFormatRepository)
    {
    }

    public function format(mixed $value, ?FormattingContext $context = null): string
    {
        if (null === $value) {
            return '';
        }

        $locale = $context?->locale ?? FormattingContext::DEFAULT_LOCALE;

        $formatter = new IntlNumberFormatterLib($this->numberFormatRepository, ['locale' => $locale]);

        return $formatter->format((string) $value);
    }
}
