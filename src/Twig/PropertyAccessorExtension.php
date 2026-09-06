<?php

namespace Karross\Twig;

use Karross\Formatters\FormatterResolver;
use Karross\Formatters\FormattingContext;
use Karross\Metadata\PropertyMetadata;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Twig\Attribute\AsTwigFunction;

class PropertyAccessorExtension
{
    private PropertyAccessor $accessor;

    public function __construct(
        private FormatterResolver $formatterResolver,
        private RequestStack $requestStack,
    ) {
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

            return $this->formatterResolver->get($property->formatter)->format($value, $this->context($property));
        } catch (\Throwable $e) {
            return 'N/A';
        }
    }

    private function context(PropertyMetadata $property): FormattingContext
    {
        $currency = $property->formatterOptions['currency'] ?? null;

        return FormattingContext::forLocale(
            $this->requestStack->getCurrentRequest()?->getLocale() ?? FormattingContext::DEFAULT_LOCALE,
            \is_string($currency) ? $currency : null,
        )->with(entitySlug: $property->entitySlug, propertyName: $property->name, ucfirst: (bool) ($property->formatterOptions['ucfirst'] ?? false));
    }
}
