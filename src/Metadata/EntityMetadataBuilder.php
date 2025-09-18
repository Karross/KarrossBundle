<?php

namespace Karross\Metadata;

use Doctrine\Persistence\ManagerRegistry;
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
                    if ($config->entitySlug($fqcn)) {
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

                $entities[$fqcn] = new EntityMetadata(
                    slug: $slug,
                    actions: Action::cases(),
                    classMetadata: $meta,
                );

                $fqcnToSlugMap[$fqcn] = $slug;
            }
        }

        return $entities;
    }
}
