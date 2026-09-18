<?php

namespace Karross\Formatters;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AutoconfigureTag('karross.formatter')]
class NotAvailableFormatter implements ValueFormatterInterface
{
    public function __construct(private TranslatorInterface $translator)
    {
    }

    public function format(mixed $value, ?FormattingContext $context = null): ?string
    {
        if (null === $value) {
            return null;
        }

        return $this->translator->trans('k_index_value.not_supported', [], 'Karross');
    }
}
