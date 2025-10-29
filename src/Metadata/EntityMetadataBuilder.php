<?php

namespace Karross\Metadata;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\Mapping\ClassMetadata;
use Karross\Actions\Action;
use Karross\Config\KarrossConfig;
use Karross\Exceptions\EntityShortnameException;

class EntityMetadataBuilder
{
    private static $fqcnToSlugMap = [];

    public function __construct(
        private readonly ManagerRegistry $managerRegistry,
        private readonly KarrossConfig $config
    ) {}

    /**
     * @return EntityMetadata[]
     */
    public function buildAllMetadata(): array
    {
        $entities = [];
        foreach ($this->managerRegistry->getManagers() as $em) {
            foreach ($em->getMetadataFactory()->getAllMetadata() as $classMetadata) {
                $slug = $this->resolveSlug($classMetadata, $this->config);
                $entities[$classMetadata->getName()] = new EntityMetadata(
                    slug: $slug,
                    actions: $this->resolveActions($this->config),
                    properties: $this->buildAssociations($classMetadata) + $this->buildFields($classMetadata),
                    classMetadata: $classMetadata,
                );
                self::$fqcnToSlugMap[$classMetadata->getName()] = $slug;
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

            $associations[$associationName] = new Association(
                name: $associationName,
                identifier: $associationMetadata->getIdentifier(),
                fqcn: $associationClass,
            );
        }

        return $associations;
    }

    private function buildFields(ClassMetadata $classMetadata): array
    {
        $fields = [];
        foreach ($classMetadata->getFieldNames() as $fieldName) {
            $fields[$fieldName] = new Field(
                name: $fieldName,
                fqcn: $classMetadata->getName(),
            );
        }

        return $fields;
    }

    /**
     * @throws EntityShortnameException
     */
    private function resolveSlug(ClassMetadata $classMetadata, KarrossConfig $config): string
    {
        $fqcn = $classMetadata->getName();
        $shortname = strtolower($classMetadata->getReflectionClass()->getShortName());
        $slug = $config->entitySlug($fqcn) ?? $shortname;
        if (in_array($slug, self::$fqcnToSlugMap)) {
            if ($this->config->entitySlug($fqcn)) {
                throw new EntityShortnameException(
                    resource: $fqcn,
                    message: sprintf(
                        "The slug you have provided for %s is already in use with %s",
                        $fqcn,
                        array_search($this->config->entitySlug($fqcn), self::$fqcnToSlugMap),
                    ),
                );
            }
            throw new EntityShortnameException(
                resource: $fqcn,
                message: sprintf(
                    "Those classes (%s, %s) have the same shortname '%s'. Please provide a slug to solve the conflicts",
                    $fqcn,
                    array_search($slug, self::$fqcnToSlugMap),
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
