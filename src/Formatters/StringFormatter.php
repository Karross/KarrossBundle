<?php

namespace Karross\Formatters;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('karross.formatter')]
class StringFormatter implements ValueFormatterInterface
{
    public function format(mixed $value, ?FormattingContext $context = null): ?string
    {
        if (null === $value) {
            return null;
        }

        $string = (string) $value;

        if ($context?->ucfirst && '' !== $string) {
            $string = mb_strtoupper(mb_substr($string, 0, 1)).mb_substr($string, 1);
        }

        return $string;
    }
}
