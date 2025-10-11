<?php

declare(strict_types=1);

namespace Karross\Twig;

use Symfony\Component\PropertyAccess\PropertyAccess;
use Twig\Attribute\AsTwigFunction;
use Twig\Extension\AbstractExtension;

class PropertyAccessorExtension
{
    public function __construct()
    {
        $this->accessor = PropertyAccess::createPropertyAccessor();
    }

    #[AsTwigFunction('getAttribute')]
    public function getAttribute($entity, $property): mixed
    {
        try {
            $value = $this->accessor->getValue($entity, $property);
            if (is_scalar($value) || $value === null || $value instanceof \Stringable) {
                return $value;
            }

            return 'N/A';
        } catch (\Throwable $e) {
            return 'N/A';
        }
    }
}
