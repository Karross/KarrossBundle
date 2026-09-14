<?php

namespace Karross\Metadata\Computed;

/**
 * Frozen snapshot of a property built in a single pass: the read-model carries
 * the projected result of the raw sources, never the sources themselves.
 * Read-only — the values are never interpreted at read time.
 *
 * The kind (field/association) is carried by the concrete discriminator class:
 * FieldMetadata and AssociationMetadata are empty markers over this state.
 */
abstract readonly class PropertyMetadata
{
    /**
     * @param class-string               $fqcn
     * @param string[]                   $identifier       associations only, empty for fields
     * @param class-string               $formatter
     * @param array<string, string|bool> $formatterOptions
     * @param list<string>               $typeHierarchy
     */
    public function __construct(
        public string $name,
        public string $fqcn,
        public ?string $phpType,
        public ?string $doctrineType,
        public bool $conflict,
        public array $identifier,
        public string $formatter,
        public array $formatterOptions = [],
        public array $typeHierarchy = [],
        public ?string $widget = null,
        public ?string $entitySlug = null,
    ) {
    }

    public function isField(): bool
    {
        return $this instanceof FieldMetadata;
    }

    public function isAssociation(): bool
    {
        return $this instanceof AssociationMetadata;
    }
}
