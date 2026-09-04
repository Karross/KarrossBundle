<?php

namespace Karross\Twig;

use Twig\Attribute\AsTwigFunction;

class StringableExtension
{
    #[AsTwigFunction('asString')]
    public function asString($value): string
    {
        return match (true) {
            $value instanceof \Stringable => $value->__toString(),
            $value instanceof \BackedEnum => $value->name,
            \is_bool($value) => $value ? 'true' : 'false',
            \is_scalar($value) => (string) $value,
            default => 'N/A',
        };
    }
}
