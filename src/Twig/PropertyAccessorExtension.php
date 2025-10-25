<?php

declare(strict_types=1);

namespace Karross\Twig;

use Karross\Responders\Transformers\ValueFormatter;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Twig\Attribute\AsTwigFunction;

class PropertyAccessorExtension
{
    private PropertyAccessor $accessor;
    public function __construct(private readonly ValueFormatter $valueFormatter)
    {
        $this->accessor = PropertyAccess::createPropertyAccessor();
    }

    #[AsTwigFunction('getAttribute')]
    public function getAttribute($entity, $property): mixed
    {
        try {
            $value = $this->accessor->getValue($entity, $property);

            return $this->valueFormatter->format($value);
        } catch (\Throwable $e) {
            return 'N/A';
        }
    }
}
