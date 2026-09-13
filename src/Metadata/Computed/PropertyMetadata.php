<?php

namespace Karross\Metadata\Computed;

use Karross\Metadata\PropertyType;

class PropertyMetadata
{
    /**
     * @param class-string               $formatter
     * @param array<string, string|bool> $formatterOptions
     */
    public function __construct(
        public string $name,
        public bool $isField,
        public bool $isAssociation,
        public PropertyType $type,
        public string $formatter,
        public array $formatterOptions = [],
        public ?string $entitySlug = null,
    ) {
    }
}
