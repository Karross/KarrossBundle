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
use Karross\Formatters\ValueFormatterInterface;
use Karross\Metadata\Computed\AssociationMetadata;
use Karross\Metadata\Computed\Cardinality;
use Karross\Metadata\Computed\EntityMetadata;
use Karross\Metadata\Computed\FieldMetadata;

readonly class EntityMetadataBuilder
{
    public function __construct(
        private ManagerRegistry $managerRegistry,
        private KarrossConfig $config,
        private FormatterResolver $formatterResolver,
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
                $entities[$classMetadata->getName()] = new EntityMetadata(
                    slug: $slug,
                    actions: $this->resolveActions($this->config),
                    properties: $this->buildAssociations($slug, $classMetadata) + $this->buildFields($slug, $classMetadata),
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

            $cardinality = $classMetadata->isCollectionValuedAssociation($associationName)
                ? Cardinality::TO_MANY
                : Cardinality::TO_ONE;
            $phpType = PropertyCollector::resolvePhpType($reflectionProperty);

            $formatter = $this->resolveFormatter($classMetadata->getName(), $associationName, $phpType, null);

            $associations[$associationName] = new AssociationMetadata(
                name: $associationName,
                fqcn: $associationClass,
                phpType: $phpType,
                doctrineType: null,
                conflict: false,
                identifier: $associationMetadata->getIdentifier(),
                typeHierarchy: PropertyCollector::associationTypeHierarchy($cardinality),
                formatter: $formatter,
                formatterOptions: $this->config->entityPropertyFormatterOptions($classMetadata->getName(), $associationName),
                cardinality: $cardinality,
                widget: null,
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

            $phpType = PropertyCollector::resolvePhpType($reflectionProperty);

            $formatter = $this->resolveFormatter($classMetadata->getName(), $fieldName, $phpType, $fieldMapping);

            $fields[$fieldName] = new FieldMetadata(
                name: $fieldName,
                fqcn: $classMetadata->getName(),
                phpType: $phpType,
                doctrineType: $fieldMapping->type,
                conflict: PropertyCollector::isConflict($phpType, $fieldMapping),
                identifier: [],
                formatter: $formatter,
                formatterOptions: $this->config->entityPropertyFormatterOptions($classMetadata->getName(), $fieldName),
                typeHierarchy: PropertyCollector::buildTypeHierarchy($phpType, $fieldMapping),
                entitySlug: $entitySlug,
                length: $fieldMapping->length,
                precision: $fieldMapping->precision,
                scale: $fieldMapping->scale,
                enumType: $fieldMapping->enumType,
                unsigned: (bool) ($fieldMapping->options['unsigned'] ?? false),
                fixed: (bool) ($fieldMapping->options['fixed'] ?? false),
                nullable: (bool) $fieldMapping->nullable,
                id: (bool) $fieldMapping->id,
                version: (bool) $fieldMapping->version,
                generated: null !== $fieldMapping->generated,
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
