<?php

namespace Karross\Metadata\Collect;

use Doctrine\ORM\Mapping\FieldMapping;

/**
 * Resolves, per property, the concrete renderer template for each supported
 * action. The Metadata layer only knows this contract and hands over the raw
 * analysis facts (PHP type, Doctrine field mapping, cardinality): the
 * renderer-specific vocabulary (type hierarchy, candidate patterns) and the
 * physical existence resolution happen in the implementing layer. The
 * resulting map is a renderer-scoped projection of the analysis, in the same
 * spirit as the formatter.
 */
interface PropertyTemplateResolverInterface
{
    /**
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
     * @return array<string, string> action value → resolved template name
     */
    public function resolveAssociation(
        string $entitySlug,
        string $associationName,
        bool $isToMany,
    ): array;
}
