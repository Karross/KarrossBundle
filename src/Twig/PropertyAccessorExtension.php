<?php

declare(strict_types=1);

namespace Karross\Twig;

use Karross\Metadata\PropertyMetadata;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Twig\Attribute\AsTwigFunction;

class PropertyAccessorExtension
{
    private PropertyAccessor $accessor;
    public function __construct()
    {
        $this->accessor = PropertyAccess::createPropertyAccessor();
    }

    #[AsTwigFunction('k_value')]
    public function getValue($entity, PropertyMetadata $property): mixed
    {
        return $this->accessor->getValue($entity, $property->name);
    }

    #[AsTwigFunction('k_formatted_value')]
    public function getFormattedValue($entity, PropertyMetadata $property): string
    {
        try {
            $value = $this->accessor->getValue($entity, $property->name);

            return $property->format($value);
        } catch (\Throwable $e) {
            return 'N/A';
        }
    }
}
