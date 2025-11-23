<?php

declare(strict_types=1);

namespace Karross\Twig;

use Karross\Metadata\PropertyMetadata;
use Karross\Responders\Transformers\ValueFormatter;
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
    public function getFormattedValue($entity, PropertyMetadata $property)
    {
            $value = $this->accessor->getValue($entity, $property->name);

            return $property->format($value);
        try {
        } catch (\Throwable $e) {
            return 'N/A';
        }
    }
}
