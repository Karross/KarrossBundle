<?php

namespace Karross\Formatters;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('karross.formatter')]
class BooleanFormatter implements ValueFormatterInterface
{
    public function format(mixed $value, ?FormattingContext $context = null): string
    {
        if (null === $value) {
            return '';
        }

        return $value ? 'true' : 'false';
    }
}
