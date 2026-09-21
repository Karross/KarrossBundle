<?php

namespace Karross\Formatters;

use CommerceGuys\Intl\Formatter\NumberFormatter as IntlNumberFormatterLib;
use CommerceGuys\Intl\NumberFormat\NumberFormatRepository;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('karross.formatter')]
class IntlNumberFormatter implements ValueFormatterInterface
{
    public function __construct(private readonly NumberFormatRepository $numberFormatRepository)
    {
    }

    public function format(mixed $value, ?FormattingContext $context = null): ?string
    {
        if (null === $value) {
            return null;
        }

        $locale = $context?->locale ?? FormattingContext::DEFAULT_LOCALE;

        $options = ['locale' => $locale];
        if (null !== $context?->maximumFractionDigits) {
            $options['maximum_fraction_digits'] = $context->maximumFractionDigits;
        }
        if (null !== $context?->minimumFractionDigits) {
            $options['minimum_fraction_digits'] = $context->minimumFractionDigits;
        }

        $formatter = new IntlNumberFormatterLib($this->numberFormatRepository, $options);

        return $formatter->format((string) $value);
    }
}
