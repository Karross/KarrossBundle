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

    public function format(mixed $value, ?FormattingContext $context = null): string
    {
        return $this->translator->trans('k_index_value.not_supported', [], 'Karross');
    }
}
