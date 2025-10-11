<?php

namespace Karross\Metadata;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\Mapping\ClassMetadata;
use Karross\Actions\Action;
use Karross\Config\KarrossConfig;
use Karross\Exceptions\EntityShortnameException;

class EntityMetadataBuilder
{
    public function __construct(private ManagerRegistry $managerRegistry, private KarrossConfig $config)
    {
    }

    /**
     * @return EntityMetadata[]
     */
    public function buildAllMetadata(): array
    {
        $entities = [];
        $fqcnToSlugMap = [];
        foreach ($this->managerRegistry->getManagers() as $em) {
            foreach ($em->getMetadataFactory()->getAllMetadata() as $meta) {
                $fqcn = $meta->getName();
                $shortname = strtolower($meta->getReflectionClass()->getShortName());
                $slug = $this->config->entitySlug($fqcn) ?? $shortname;
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

                $entities[$fqcn] = $this->buildMetadata($slug, Action::cases(), $meta);

                $fqcnToSlugMap[$fqcn] = $slug;
            }
        }

        return $entities;
    }

    public function buildMetadata(string $slug, array $actions, ClassMetadata $meta): EntityMetadata
    {
        return new EntityMetadata(
                    slug: $slug,
                    actions: $actions,
                    classMetadata: $meta,
                    associations: $this->computeAssociations($meta),
                );
    }
    private function computeAssociations(ClassMetadata $classMetadata): array
    {
        $associations = [];

        foreach($classMetadata->getAssociationNames() as $associationName) {
            $associationClass = $classMetadata->getAssociationTargetClass($associationName);
            if ($associationClass === null) {
                throw new \LogicException('Association class not found for ' . $associationName);
            }
            $associationMetadata = $this->managerRegistry->getManagerForClass($associationClass)->getClassMetadata($associationClass);
            $associations[$associationClass][$associationName] = [
                'identifier' => $associationMetadata->getIdentifier(),
                'fqcn' => $associationClass,
                'name' => $associationName,
            ];
        }

        return $associations;
    }
}
