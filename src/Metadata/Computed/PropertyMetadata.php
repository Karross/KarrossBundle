<?php

namespace Karross\Metadata\Computed;

/**
 * Frozen snapshot of a property built in a single pass: the read-model carries
 * only the projected result of the raw sources (formatter, resolved renderer
 * templates, host context), never the sources themselves.
 * Read-only — the values are never interpreted at read time.
 *
 * The kind (field/association) is carried by the concrete discriminator class:
 * FieldMetadata (pure marker) and AssociationMetadata (target identifier,
 * resolved formatter).
 */
abstract readonly class PropertyMetadata
{
    /**
     * @param class-string               $fqcn
     * @param class-string               $formatter
     * @param array<string, string|bool> $formatterOptions
     * @param array<string, string>      $templates        action → resolved template name
     */
    public function __construct(
        public string $name,
        public string $fqcn,
        public string $formatter,
        public array $formatterOptions = [],
        public array $templates = [],
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
