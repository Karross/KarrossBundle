<?php

namespace Karross\Metadata\Computed;

/**
 * Association read-model (discriminator over PropertyMetadata) carrying the
 * cardinality fact (to-one / to-many).
 */
readonly class AssociationMetadata extends PropertyMetadata
{
    /**
     * @param class-string               $fqcn
     * @param string[]                   $identifier       the target entity's identifier
     * @param class-string               $formatter
     * @param array<string, string|bool> $formatterOptions
     * @param list<string>               $typeHierarchy
     */
    public function __construct(
        string $name,
        string $fqcn,
        ?string $phpType,
        ?string $doctrineType,
        bool $conflict,
        array $identifier,
        string $formatter,
        array $formatterOptions,
        array $typeHierarchy,
        ?string $widget,
        ?string $entitySlug,
        public Cardinality $cardinality,
    ) {
        parent::__construct(
            $name,
            $fqcn,
            $phpType,
            $doctrineType,
            $conflict,
            $identifier,
            $formatter,
            $formatterOptions,
            $typeHierarchy,
            $widget,
            $entitySlug,
        );
    }
}
