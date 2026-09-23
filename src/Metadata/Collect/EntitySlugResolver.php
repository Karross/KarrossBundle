<?php

namespace Karross\Metadata\Collect;

use Doctrine\Persistence\Mapping\ClassMetadata;
use Karross\Config\EntityConfig;
use Karross\Exceptions\EntityShortnameException;

/**
 * Picks the URL slug for one entity and catches collisions.
 *
 * Priority: config slug first, then the lowercased shortname.
 *
 * Example without config:
 *   App\Entity\Article returns "article"
 *
 * Example with config entities.App\Entity\Article.slug: "posts":
 *   returns "posts"
 *
 * Example collision (App\Entity\Post and App\Entity\BlogPost both map to "post"):
 *   throws EntityShortnameException asking for an explicit slug
 */
final readonly class EntitySlugResolver
{
    public function __construct(private EntityConfig $entityConfig)
    {
    }

    /**
     * Returns the slug for this entity, or throws on collision.
     *
     * Example:
     *   resolve(Article, []) returns "article"
     *   resolve(Article, ["App\Entity\Post" => "article"]) throws
     *     (two classes want "article")
     *
     * @param ClassMetadata<object> $classMetadata
     * @param array<string, string> $fqcnToSlugMap slugs already taken in this build
     *
     * @throws EntityShortnameException
     */
    public function resolve(ClassMetadata $classMetadata, array $fqcnToSlugMap): string
    {
        $fqcn = $classMetadata->getName();
        $shortname = strtolower($classMetadata->getReflectionClass()->getShortName());
        $configured = $this->entityConfig->slug($fqcn);
        $slug = $configured ?? $shortname;

        if (!\in_array($slug, $fqcnToSlugMap, true)) {
            return $slug;
        }

        if (null !== $configured) {
            throw new EntityShortnameException(resource: $fqcn, message: \sprintf('The slug you have provided for %s is already in use with %s', $fqcn, array_search($configured, $fqcnToSlugMap, true)));
        }

        throw new EntityShortnameException(resource: $fqcn, message: \sprintf("Those classes (%s, %s) have the same shortname '%s'. Please provide a slug to solve the conflicts", $fqcn, array_search($slug, $fqcnToSlugMap, true), $slug));
    }
}
