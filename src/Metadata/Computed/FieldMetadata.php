<?php

namespace Karross\Metadata\Computed;

/**
 * Field read-model (discriminator over PropertyMetadata). It carries no
 * field-specific state: the shared projections (formatter, templates,
 * identifiers) hold everything the rendering layer reads. The Doctrine field
 * mapping and the PHP reflection facts are consumed at build time to produce
 * those projections, never stored on the read-model.
 */
readonly class FieldMetadata extends PropertyMetadata
{
    /**
     * @param class-string          $fqcn
     * @param class-string          $formatter
     * @param array<string, mixed>  $formatterOptions
     * @param array<string, string> $templates        action → resolved template name
     */
    public function __construct(
        string $name,
        string $fqcn,
        string $formatter,
        array $formatterOptions = [],
        array $templates = [],
        ?string $entitySlug = null,
    ) {
        parent::__construct(
            $name,
            $fqcn,
            $formatter,
            $formatterOptions,
            $templates,
            $entitySlug,
        );
    }
}
