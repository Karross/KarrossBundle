<?php

namespace Karross\Metadata;

class AssociationMetadata extends PropertyMetadata
{
    /**
     * @param class-string          $fqcn
     * @param class-string          $formatter
     * @param array<string, string> $formatterOptions
     */
    public function __construct(
        public string $name,
        public array $identifier,
        public string $fqcn,
        public PropertyType $type,
        public string $formatter,
        public array $formatterOptions = [],
    ) {
        parent::__construct($name, false, true, $type, $formatter, $formatterOptions);
    }
}
