<?php

namespace Karross\Formatters;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('karross.formatter')]
class EnumFormatter implements ValueFormatterInterface
{
    public function __construct(private ValueTranslator $valueTranslator)
    {
    }

    public function format(mixed $value, ?FormattingContext $context = null): string
    {
        if (null === $value) {
            return '';
        }

        if (!$value instanceof \UnitEnum) {
            return 'N/A';
        }

        // For BackedEnum, use the value as the raw display, otherwise the name
        $raw = $value instanceof \BackedEnum ? (string) $value->value : $value->name;

        return $this->valueTranslator->translate($raw, $context);
    }
}
