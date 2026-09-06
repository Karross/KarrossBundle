<?php

namespace Karross\Formatters\Boolean;

use Karross\Formatters\FormattingContext;
use Karross\Formatters\ValueFormatterInterface;
use Karross\Formatters\ValueTranslator;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('karross.formatter')]
class TrueFalseFormatter implements ValueFormatterInterface
{
    public function __construct(private ValueTranslator $valueTranslator)
    {
    }

    public function format(mixed $value, ?FormattingContext $context = null): string
    {
        if (null === $value) {
            return '';
        }

        return $this->valueTranslator->translate($value ? 'true' : 'false', $context);
    }
}
