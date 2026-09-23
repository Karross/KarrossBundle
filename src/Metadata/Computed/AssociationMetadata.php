<?php

namespace Karross\Metadata\Computed;

/**
 * Association read-model (discriminator over PropertyMetadata) carrying the
 * resolved formatter for the association value. Display semantics live in the
 * formatter, not in a stored cardinality fact.
 */
readonly class AssociationMetadata extends PropertyMetadata
{
    /**
     * @param class-string          $fqcn
     * @param string[]              $identifier       the target entity's identifier column names
     * @param class-string          $formatter
     * @param array<string, mixed>  $formatterOptions
     * @param array<string, string> $templates        action → resolved template name
     */
    public function __construct(
        string $name,
        string $fqcn,
        public array $identifier,
        string $formatter,
        array $formatterOptions,
        array $templates,
        ?string $entitySlug,
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
