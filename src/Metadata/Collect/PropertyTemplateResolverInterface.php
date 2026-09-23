<?php

namespace Karross\Metadata\Collect;

use Doctrine\ORM\Mapping\FieldMapping;

/**
 * Resolves the cell template for one property, per action.
 *
 * The Metadata layer only hands over raw facts (PHP type, Doctrine mapping,
 * cardinality). The implementing layer picks the candidates and checks
 * file existence.
 *
 * Example for App\Entity\Article::createdAt (DateTimeImmutable):
 *   resolveField("article", "createdAt", "DateTimeImmutable", mapping)
 *     returns ["index" => "@Karross/index/field_type_datetime.html.twig"]
 *
 * Example for App\Entity\Article::title (string):
 *   returns ["index" => "@Karross/index/field.html.twig"]
 *
 * Example for App\Entity\Article::category (to-one):
 *   resolveAssociation("article", "category", false)
 *     returns ["index" => "@Karross/index/association_one.html.twig"]
 */
interface PropertyTemplateResolverInterface
{
    /**
     * Returns action value → resolved template name for a column field.
     *
     * Example:
     *   resolveField("article", "createdAt", "DateTimeImmutable", mapping)
     *     returns ["index" => "...field_type_datetime.html.twig"]
     *
     * @param string|null $phpType the reflected property type, or null when
     *                             no type could be resolved
     *
     * @return array<string, string> action value → resolved template name
     */
    public function resolveField(
        string $entitySlug,
        string $fieldName,
        ?string $phpType,
        ?FieldMapping $fieldMapping,
    ): array;

    /**
     * Returns action value → resolved template name for an association.
     *
     * Example:
     *   resolveAssociation("article", "tags", true)
     *     returns ["index" => "...association_many.html.twig"]
     *
     * @param bool $isToMany true for collections (tags), false for to-one (category)
     *
     * @return array<string, string> action value → resolved template name
     */
    public function resolveAssociation(
        string $entitySlug,
        string $associationName,
        bool $isToMany,
    ): array;
}
