<?php

namespace Karross\Metadata\Collect;

use Doctrine\ORM\Mapping\ClassMetadata as OrmClassMetadata;
use Doctrine\ORM\Mapping\FieldMapping;
use Karross\Config\EntityConfig;
use Karross\Formatters\FormatterResolver;
use Karross\Formatters\IntlNumberFormatter;
use Karross\Formatters\Options\DateTimeOptionsResolver;
use Karross\Formatters\ValueFormatterInterface;
use Karross\Metadata\Computed\FieldMetadata;
use Karross\Metadata\Computed\PropertyMetadata;

/**
 * Turns Doctrine column fields into FieldMetadata, one pass per entity.
 *
 * Example for App\Entity\Article::title (string):
 *   FieldMetadata(name: "title", formatter: StringFormatter, ...)
 *
 * Example for App\Entity\Article::price (decimal, scale 2):
 *   FieldMetadata(name: "price", formatter: IntlNumberFormatter,
 *                 formatterOptions: ["maximum_fraction_digits" => 2])
 */
final readonly class FieldMetadataBuilder
{
    public function __construct(
        private EntityConfig $entityConfig,
        private FormatterResolver $formatterResolver,
        private DateTimeOptionsResolver $datetimeOptionsResolver,
        private PropertyTemplateResolverInterface $propertyTemplateResolver,
    ) {
    }

    /**
     * Builds one FieldMetadata per Doctrine field of the entity.
     *
     * Example for App\Entity\Article with slug "article":
     *   ["title" => FieldMetadata(formatter: StringFormatter, ...),
     *    "viewCount" => FieldMetadata(formatter: IntlNumberFormatter, ...),
     *    "createdAt" => FieldMetadata(formatter: DateTimeFormatter,
     *                                  formatterOptions: ["datetime_format" => [...]])]
     *
     * @param OrmClassMetadata<object> $classMetadata
     *
     * @return array<string, FieldMetadata>
     */
    public function build(string $entitySlug, OrmClassMetadata $classMetadata): array
    {
        $fields = [];
        $reflectionClass = new \ReflectionClass($classMetadata->getName());
        $fqcn = $classMetadata->getName();

        foreach ($classMetadata->getFieldNames() as $fieldName) {
            $fieldMapping = $classMetadata->getFieldMapping($fieldName);
            $phpType = PhpTypeInspector::phpType(
                PhpTypeInspector::reflectionProperty($reflectionClass, $fieldName),
            );
            $formatter = $this->resolveFormatter($fqcn, $fieldName, $phpType, $fieldMapping);
            $formatterOptions = $this->entityConfig->propertyFormatterOptions($fqcn, $fieldName);
            if (IntlNumberFormatter::class === $formatter && [] === $formatterOptions && null !== $fieldMapping->scale) {
                $formatterOptions['maximum_fraction_digits'] = $fieldMapping->scale;
            }

            $fields[$fieldName] = new FieldMetadata(
                name: $fieldName,
                fqcn: $fqcn,
                formatter: $formatter,
                formatterOptions: $this->datetimeOptionsResolver->resolve($formatter, $formatterOptions, $fqcn, $fieldName),
                templates: $this->propertyTemplateResolver->resolveField($entitySlug, $fieldName, $phpType, $fieldMapping),
                entitySlug: $entitySlug,
            );
        }

        return $fields;
    }

    /**
     * Returns true when any property name contains a dot (embedded field).
     *
     * Example:
     *   hasEmbedded(["identity.firstname" => ..., "title" => ...]) returns true
     *   hasEmbedded(["title" => ..., "content" => ...]) returns false
     *
     * @param PropertyMetadata[] $properties
     */
    public function hasEmbedded(array $properties): bool
    {
        foreach ($properties as $property) {
            if (str_contains($property->name, '.')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Picks the formatter: host config wins, then the resolver chain.
     *
     * Example for App\Entity\Article::price with no config override:
     *   returns IntlNumberFormatter::class (via the float resolver)
     *
     * Example when config sets entities.App\Entity\Article.properties.price.formatter:
     *   returns that configured class-string
     *
     * @return class-string<ValueFormatterInterface>
     */
    private function resolveFormatter(string $fqcn, string $property, ?string $phpType, ?FieldMapping $fieldMapping = null): string
    {
        /** @var class-string<ValueFormatterInterface>|null $configured */
        $configured = $this->entityConfig->propertyFormatter($fqcn, $property);

        return $configured ?? $this->formatterResolver->resolve($phpType, $fieldMapping);
    }
}
