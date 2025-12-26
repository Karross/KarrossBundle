<?php

namespace Karross\Metadata;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\Mapping\ClassMetadata;
use Karross\Actions\Action;
use Karross\Config\KarrossConfig;
use Karross\Exceptions\EntityShortnameException;
use Karross\Formatters\FormatterResolver;
use Karross\Formatters\NotAvailableFormatter;
use ReflectionClass;
use ReflectionProperty;

readonly class EntityMetadataBuilder
{
    public function __construct(
        private ManagerRegistry      $managerRegistry,
        private KarrossConfig        $config,
        private PropertyTypeDetector $typeDetector,
        private FormatterResolver    $formatterResolver
    ) {}

    /**
     * @return EntityMetadata[]
     */
    public function buildAllMetadata(): array
    {
        $entities = [];
        $fqcnToSlugMap = [];
        foreach ($this->managerRegistry->getManagers() as $em) {
            foreach ($em->getMetadataFactory()->getAllMetadata() as $classMetadata) {
                $slug = $this->resolveSlug($classMetadata, $this->config, $fqcnToSlugMap);
                $entities[$classMetadata->getName()] = new EntityMetadata(
                    slug: $slug,
                    actions: $this->resolveActions($this->config),
                    properties: $this->buildAssociations($classMetadata) + $this->buildFields($classMetadata),
                    classMetadata: $classMetadata,
                );
                $fqcnToSlugMap[$classMetadata->getName()] = $slug;
            }
        }

        return $entities;
    }

    private function buildAssociations(ClassMetadata $classMetadata): array
    {
        $associations = [];
        $reflectionClass = new ReflectionClass($classMetadata->getName());

        foreach($classMetadata->getAssociationNames() as $associationName) {
            $associationClass = $classMetadata->getAssociationTargetClass($associationName);
            if ($associationClass === null) {
                throw new \LogicException('Association class not found for ' . $associationName);
            }
            $associationMetadata = $this->managerRegistry->getManagerForClass($associationClass)->getClassMetadata($associationClass);

            $reflectionProperty = $reflectionClass->getProperty($associationName);

            // Detect type
            $type = $this->typeDetector->detect(
                property: $reflectionProperty,
                doctrineType: null,
                isAssociation: true,
            );

            // Resolve formatter
            $formatter = $this->formatterResolver->resolve($type);

            $associations[$associationName] = new AssociationMetadata(
                name: $associationName,
                identifier: $associationMetadata->getIdentifier(),
                fqcn: $associationClass,
                type: $type,
                formatter: $formatter,
            );
        }

        return $associations;
    }

    private function buildFields(ClassMetadata $classMetadata): array
    {
        $fields = [];
        $reflectionClass = new ReflectionClass($classMetadata->getName());
        
        foreach ($classMetadata->getFieldNames() as $fieldName) {
            $doctrineType = $classMetadata->getTypeOfField($fieldName);
            
            // Use recursive resolution for embedded fields (e.g., 'identity.firstname')
            $reflectionProperty = $this->resolveReflectionProperty(
                $reflectionClass,
                $fieldName
            );

            // Detect type
            $type = $this->typeDetector->detect(
                property: $reflectionProperty,
                doctrineType: $doctrineType,
                isAssociation: false,
            );

            // Resolve formatter
            $formatter = $this->formatterResolver->resolve($type);

            $fields[$fieldName] = new FieldMetadata(
                name: $fieldName,
                fqcn: $classMetadata->getName(),
                type: $type,
                formatter: $formatter,
            );
        }

        return $fields;
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
     * @param ReflectionClass $reflectionClass The entity's reflection class
     * @param string $fieldName The field name (may contain dots for embedded fields)
     * @return \ReflectionProperty|null The resolved property, or null if not found
     */
    private function resolveReflectionProperty(
        ReflectionClass $reflectionClass,
        string $fieldName
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
            if ($index < count($parts) - 1) {
                $type = $currentProperty->getType();
                
                if (!($type instanceof \ReflectionNamedType) || $type->isBuiltin()) {
                    return null;
                }
                
                $className = $type->getName();
                if (!class_exists($className)) {
                    return null;
                }
                
                $currentClass = new ReflectionClass($className);
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

        if (in_array($slug, $fqcnToSlugMap)) {
            if ($this->config->entitySlug($fqcn)) {
                throw new EntityShortnameException(
                    resource: $fqcn,
                    message: sprintf(
                        "The slug you have provided for %s is already in use with %s",
                        $fqcn,
                        array_search($this->config->entitySlug($fqcn), $fqcnToSlugMap),
                    ),
                );
            }
            throw new EntityShortnameException(
                resource: $fqcn,
                message: sprintf(
                    "Those classes (%s, %s) have the same shortname '%s'. Please provide a slug to solve the conflicts",
                    $fqcn,
                    array_search($slug, $fqcnToSlugMap),
                    $slug
                )
            );
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
