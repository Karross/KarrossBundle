<?php

namespace Karross\Metadata\Collect;

use Doctrine\ORM\Mapping\ClassMetadata as OrmClassMetadata;
use Doctrine\ORM\Mapping\FieldMapping;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\Mapping\ClassMetadata;
use Karross\Actions\Action;
use Karross\Config\KarrossConfig;
use Karross\Exceptions\EntityShortnameException;
use Karross\Formatters\FormatterResolver;
use Karross\Formatters\IntlNumberFormatter;
use Karross\Formatters\ValueFormatterInterface;
use Karross\Metadata\Computed\AssociationMetadata;
use Karross\Metadata\Computed\EntityMetadata;
use Karross\Metadata\Computed\FieldMetadata;
use Karross\Metadata\Computed\PropertyMetadata;

/**
 * Builds the frozen Computed read-models (EntityMetadata / PropertyMetadata)
 * from the raw sources (PHP reflection + Doctrine class metadata) and the
 * bundle config. It is the single place where the analysis phase happens and
 * where every precomputable fact (formatter, resolved templates, column facts)
 * is projected into the read-models.
 */
readonly class ComputedMetadataBuilder
{
    public function __construct(
        private ManagerRegistry $managerRegistry,
        private KarrossConfig $config,
        private FormatterResolver $formatterResolver,
        private PropertyTemplateResolverInterface $propertyTemplateResolver,
        private EntityTemplateResolverInterface $entityTemplateResolver,
    ) {
    }

    /**
     * @return EntityMetadata[]
     */
    public function buildAllMetadata(): array
    {
        $entities = [];
        $fqcnToSlugMap = [];
        foreach ($this->managerRegistry->getManagers() as $em) {
            foreach ($em->getMetadataFactory()->getAllMetadata() as $classMetadata) {
                // getAllMetadata() types against the persistence interface; the
                // real instance is the ORM one — guaranteed by the doctrine/orm
                // requirement. The ORM type is what gives us getFieldMapping().
                \assert($classMetadata instanceof OrmClassMetadata);
                $slug = $this->resolveSlug($classMetadata, $this->config, $fqcnToSlugMap);
                $actions = $this->resolveActions($this->config);
                $properties = $this->buildAssociations($slug, $classMetadata) + $this->buildFields($slug, $classMetadata);
                $entities[$classMetadata->getName()] = new EntityMetadata(
                    slug: $slug,
                    actions: $actions,
                    properties: $properties,
                    templates: $this->resolveActionTemplates($slug, $actions, $this->hasEmbeddedFields($properties)),
                    fqcn: $classMetadata->getName(),
                    identifier: $classMetadata->getIdentifier(),
                );
                $fqcnToSlugMap[$classMetadata->getName()] = $slug;
            }
        }

        return $entities;
    }

    /**
     * @param OrmClassMetadata<object> $classMetadata
     *
     * @return array<string, AssociationMetadata>
     */
    private function buildAssociations(string $entitySlug, OrmClassMetadata $classMetadata): array
    {
        $associations = [];
        $reflectionClass = new \ReflectionClass($classMetadata->getName());

        foreach ($classMetadata->getAssociationNames() as $associationName) {
            $associationClass = $classMetadata->getAssociationTargetClass($associationName);
            $associationMetadata = $this->managerRegistry->getManagerForClass($associationClass)->getClassMetadata($associationClass);

            $reflectionProperty = $reflectionClass->getProperty($associationName);

            $isToMany = $classMetadata->isCollectionValuedAssociation($associationName);
            $phpType = $this->resolvePhpType($reflectionProperty);

            $formatter = $this->resolveFormatter($classMetadata->getName(), $associationName, $phpType, null);

            $associations[$associationName] = new AssociationMetadata(
                name: $associationName,
                fqcn: $associationClass,
                identifier: $associationMetadata->getIdentifier(),
                templates: $this->propertyTemplateResolver->resolveAssociation($entitySlug, $associationName, $isToMany),
                formatter: $formatter,
                formatterOptions: $this->config->entityPropertyFormatterOptions($classMetadata->getName(), $associationName),
                entitySlug: $entitySlug,
            );
        }

        return $associations;
    }

    /**
     * @param OrmClassMetadata<object> $classMetadata
     *
     * @return array<string, FieldMetadata>
     */
    private function buildFields(string $entitySlug, OrmClassMetadata $classMetadata): array
    {
        $fields = [];
        $reflectionClass = new \ReflectionClass($classMetadata->getName());

        foreach ($classMetadata->getFieldNames() as $fieldName) {
            $fieldMapping = $classMetadata->getFieldMapping($fieldName);

            // Use recursive resolution for embedded fields (e.g., 'identity.firstname')
            $reflectionProperty = $this->resolveReflectionProperty($reflectionClass, $fieldName);

            $phpType = $this->resolvePhpType($reflectionProperty);

            $formatter = $this->resolveFormatter($classMetadata->getName(), $fieldName, $phpType, $fieldMapping);

            $formatterOptions = $this->config->entityPropertyFormatterOptions($classMetadata->getName(), $fieldName);
            if (IntlNumberFormatter::class === $formatter && [] === $formatterOptions && null !== $fieldMapping->scale) {
                $formatterOptions['maximum_fraction_digits'] = $fieldMapping->scale;
            }

            $fields[$fieldName] = new FieldMetadata(
                name: $fieldName,
                fqcn: $classMetadata->getName(),
                formatter: $formatter,
                formatterOptions: $formatterOptions,
                templates: $this->propertyTemplateResolver->resolveField($entitySlug, $fieldName, $phpType, $fieldMapping),
                entitySlug: $entitySlug,
            );
        }

        return $fields;
    }

    /**
     * @return class-string<ValueFormatterInterface>
     */
    private function resolveFormatter(string $fqcn, string $property, ?string $phpType, ?FieldMapping $fieldMapping = null): string
    {
        return $this->config->entityPropertyFormatter($fqcn, $property)
            ?? $this->formatterResolver->resolve($phpType, $fieldMapping);
    }

    /**
     * Resolves the bundle-defined entity page templates (index → items/no_items
     * → item) for each action, through the renderer seam. The result is a
     * renderer-scoped projection (action value → role → resolved template
     * name) carried by the read-model, in the same spirit as the property
     * templates.
     *
     * @param Action[] $actions
     *
     * @return array<string, array<string, string>>
     */
    private function resolveActionTemplates(string $slug, array $actions, bool $hasEmbeddedFields): array
    {
        $templates = [];
        foreach ($actions as $action) {
            $resolved = $this->entityTemplateResolver->resolve($action, $slug, $hasEmbeddedFields);
            if ([] !== $resolved) {
                $templates[$action->value] = $resolved;
            }
        }

        return $templates;
    }

    /**
     * @param PropertyMetadata[] $properties
     */
    private function hasEmbeddedFields(array $properties): bool
    {
        foreach ($properties as $property) {
            if (str_contains($property->name, '.')) {
                return true;
            }
        }

        return false;
    }

    private function resolvePhpType(?\ReflectionProperty $property): ?string
    {
        if (null === $property) {
            return null;
        }

        $type = $property->getType();

        if ($type instanceof \ReflectionNamedType) {
            return $type->getName();
        }

        if ($type instanceof \ReflectionUnionType) {
            foreach ($type->getTypes() as $member) {
                if ($member instanceof \ReflectionNamedType && 'null' !== $member->getName()) {
                    return $member->getName();
                }
            }
        }

        return null;
    }

    /**
     * Resolve the ReflectionProperty for a field, handling embedded fields.
     *
     * For simple fields (e.g., 'title'), returns the direct property.
     * For embedded fields (e.g., 'identity.firstname'), navigates through the hierarchy:
     *   - Gets the 'identity' property from the entity class
     *   - Gets the type of 'identity' (e.g., Identity class)
     *   - Gets the 'firstname' property from the Identity class
     *
     * @param \ReflectionClass $reflectionClass The entity's reflection class
     * @param string           $fieldName       The field name (may contain dots for embedded fields)
     *
     * @return \ReflectionProperty|null The resolved property, or null if not found
     */
    private function resolveReflectionProperty(
        \ReflectionClass $reflectionClass,
        string $fieldName,
    ): ?\ReflectionProperty {
        // Simple case: direct property exists
        if ($reflectionClass->hasProperty($fieldName)) {
            return $reflectionClass->getProperty($fieldName);
        }

        // Embedded case: fieldName contains '.' (e.g., 'identity.firstname')
        if (!str_contains($fieldName, '.')) {
            return null;
        }

        $parts = explode('.', $fieldName);
        $currentClass = $reflectionClass;
        $currentProperty = null;

        // Navigate through each part of the path
        foreach ($parts as $index => $part) {
            if (!$currentClass->hasProperty($part)) {
                return null;
            }

            $currentProperty = $currentClass->getProperty($part);

            // If not the last part, navigate to the property's type
            if ($index < \count($parts) - 1) {
                $type = $currentProperty->getType();

                if (!($type instanceof \ReflectionNamedType) || $type->isBuiltin()) {
                    return null;
                }

                $className = $type->getName();
                if (!class_exists($className)) {
                    return null;
                }

                $currentClass = new \ReflectionClass($className);
            }
        }

        return $currentProperty;
    }

    /**
     * @throws EntityShortnameException
     */
    private function resolveSlug(ClassMetadata $classMetadata, KarrossConfig $config, array $fqcnToSlugMap): string
    {
        $fqcn = $classMetadata->getName();
        $shortname = strtolower($classMetadata->getReflectionClass()->getShortName());
        $slug = $config->entitySlug($fqcn) ?? $shortname;

        if (\in_array($slug, $fqcnToSlugMap)) {
            if ($this->config->entitySlug($fqcn)) {
                throw new EntityShortnameException(resource: $fqcn, message: \sprintf('The slug you have provided for %s is already in use with %s', $fqcn, array_search($this->config->entitySlug($fqcn), $fqcnToSlugMap)));
            }
            throw new EntityShortnameException(resource: $fqcn, message: \sprintf("Those classes (%s, %s) have the same shortname '%s'. Please provide a slug to solve the conflicts", $fqcn, array_search($slug, $fqcnToSlugMap), $slug));
        }

        return $slug;
    }

    /**
     * @return Action[]
     */
    private function resolveActions(KarrossConfig $config): array
    {
        return Action::cases();
    }
}
