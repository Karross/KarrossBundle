<?php

namespace Karross\Twig;

use Karross\Formatters\FormatterResolver;
use Karross\Formatters\FormattingContextBuilder;
use Karross\Metadata\Computed\PropertyMetadata;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Twig\Attribute\AsTwigFunction;

/**
 * Twig functions to read a property from an entity and format it for display.
 * Thin layer: delegates context building to FormattingContextBuilder and formatting to FormatterResolver.
 */
class PropertyAccessorExtension
{
    private readonly PropertyAccessor $accessor;

    public function __construct(
        private readonly FormatterResolver $formatterResolver,
        private readonly FormattingContextBuilder $contextBuilder,
    ) {
        $this->accessor = PropertyAccess::createPropertyAccessor();
    }

    /**
     * Returns the raw value, no formatting.
     *
     * Example: getValue(article, "title") returns "Hello"
     */
    #[AsTwigFunction('k_value')]
    public function getValue($entity, PropertyMetadata $property): mixed
    {
        return $this->accessor->getValue($entity, $property->name);
    }

    /**
     * Returns the value passed through its formatter, or "N/A" on any error.
     *
     * Example: getFormattedValue(article, premium=true, YesNoFormatter+ucfirst) returns "Oui"
     * Example: accessor throws (missing property) → returns "N/A"
     */
    #[AsTwigFunction('k_formatted_value')]
    public function getFormattedValue($entity, PropertyMetadata $property): ?string
    {
        try {
            $value = $this->accessor->getValue($entity, $property->name);

            return $this->formatterResolver->get($property->formatter)->format($value, $this->contextBuilder->build($property));
        } catch (\Throwable) {
            return 'N/A';
        }
    }
}
