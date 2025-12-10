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

class EntityMetadataBuilder
{
    public function __construct(
        private readonly ManagerRegistry $managerRegistry,
        private readonly KarrossConfig $config,
        private readonly FormatterResolver $formatterResolver
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

        foreach($classMetadata->getAssociationNames() as $associationName) {
            $associationClass = $classMetadata->getAssociationTargetClass($associationName);
            if ($associationClass === null) {
                throw new \LogicException('Association class not found for ' . $associationName);
            }
            $associationMetadata = $this->managerRegistry->getManagerForClass($associationClass)->getClassMetadata($associationClass);

            $associations[$associationName] = new AssociationMetadata(
                name: $associationName,
                identifier: $associationMetadata->getIdentifier(),
                fqcn: $associationClass,
                formatter: NotAvailableFormatter::class
            );
        }

        return $associations;
    }

    private function buildFields(ClassMetadata $classMetadata): array
    {
        $fields = [];
        $reflectionClass = new ReflectionClass($classMetadata->getName());
        
        foreach ($classMetadata->getFieldNames() as $fieldName) {
            $type = $classMetadata->getTypeOfField($fieldName);
            
            // Get reflection property for the formatter resolver
            $reflectionProperty = $reflectionClass->hasProperty($fieldName)
                ? $reflectionClass->getProperty($fieldName)
                : null;
            
            // Resolve formatter using all available type information
            $formatter = $reflectionProperty !== null
                ? $this->formatterResolver->resolve($reflectionProperty, $type)
                : NotAvailableFormatter::class;
            
            $fields[$fieldName] = new FieldMetadata(
                name: $fieldName,
                fqcn: $classMetadata->getName(),
                formatter: $formatter,
            );
        }

        return $fields;
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
