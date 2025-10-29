<?php

declare(strict_types=1);

namespace Karross\Twig;

use Karross\Metadata\PropertyInterface;
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

    #[AsTwigFunction('k_value')]
    public function getValue($entity, PropertyInterface $property): mixed
    {
        return $this->accessor->getValue($entity, $property->name);
    }

    #[AsTwigFunction('k_formatted_value')]
    public function getFormattedValue($entity, PropertyInterface $property): string
    {
        try {
            $value = $this->accessor->getValue($entity, $property->name);

            return $this->valueFormatter->format($value);
        } catch (\Throwable $e) {
            return 'N/A';
        }
    }
}
