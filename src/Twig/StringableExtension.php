<?php

declare(strict_types=1);

namespace Karross\Twig;

use Twig\Attribute\AsTwigFunction;
use Twig\Extension\AbstractExtension;

class StringableExtension
{
    #[AsTwigFunction('asString')]
    public function asString($value): string
    {
        return match (true) {
            $value instanceof \Stringable => $value->__toString(),
            $value instanceof \BackedEnum => $value->name,
            is_bool($value) => $value ? 'true' : 'false',
            is_scalar($value) => $value,
            default => 'N/A',
        };
    }
}
