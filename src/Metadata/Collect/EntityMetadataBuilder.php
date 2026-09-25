<?php

namespace Karross\Metadata\Collect;

use Doctrine\ORM\Mapping\ClassMetadata as OrmClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use Karross\Actions\Action;
use Karross\Metadata\Computed\EntityMetadata;

/**
 * Builds every EntityMetadata at boot, one pass over Doctrine.
 *
 * For App\Entity\Article the result is:
 *   slug: "article"
 *   properties: title, category, author, ...
 *   templates: index → index.html.twig, items → items.html.twig, ...
 */
readonly class EntityMetadataBuilder
{
    public function __construct(
        private ManagerRegistry $managerRegistry,
        private EntitySlugResolver $slugResolver,
        private FieldMetadataBuilder $fields,
        private AssociationMetadataBuilder $associations,
        private EntityTemplateResolverInterface $entityTemplateResolver,
    ) {
    }

    /**
     * Walks every Doctrine entity and returns one EntityMetadata per class.
     *
     * Example return:
     *   [App\Entity\Article => EntityMetadata(slug: "article", ...)]
     *
     * @return EntityMetadata[]
     */
    public function buildAllMetadata(): array
    {
        $entities = [];
        $fqcnToSlugMap = [];

        foreach ($this->managerRegistry->getManagers() as $em) {
            foreach ($em->getMetadataFactory()->getAllMetadata() as $classMetadata) {
                \assert($classMetadata instanceof OrmClassMetadata);
                $slug = $this->slugResolver->resolve($classMetadata, $fqcnToSlugMap);
                $properties = $this->associations->build($slug, $classMetadata) + $this->fields->build($slug, $classMetadata);
                $actions = Action::cases();
                $entities[$classMetadata->getName()] = new EntityMetadata(
                    slug: $slug,
                    actions: $actions,
                    properties: $properties,
                    templates: $this->resolveActionTemplates($slug, $actions, $this->fields->hasEmbedded($properties)),
                    fqcn: $classMetadata->getName(),
                    identifier: $classMetadata->getIdentifier(),
                );
                $fqcnToSlugMap[$classMetadata->getName()] = $slug;
            }
        }

        return $entities;
    }

    /**
     * Resolves the page templates for one action, skipping empty maps.
     *
     * Example for slug "article", action INDEX:
     *   ["index" => "@Karross/index/index.html.twig",
     *    "items" => "@Karross/index/items.html.twig"]
     *
     * Returns [] when the action has no entity templates (e.g. SHOW).
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
}
