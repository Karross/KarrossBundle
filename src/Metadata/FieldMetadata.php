<?php

namespace Karross\Metadata;

class FieldMetadata extends PropertyMetadata
{
    /**
     * @param class-string               $fqcn
     * @param class-string               $formatter
     * @param array<string, string|bool> $formatterOptions
     */
    public function __construct(
        public string $name,
        public string $fqcn,
        public PropertyType $type,
        public string $formatter,
        public array $formatterOptions = [],
        public ?string $entitySlug = null,
    ) {
        parent::__construct($name, true, false, $type, $formatter, $formatterOptions, $entitySlug);
    }
}
