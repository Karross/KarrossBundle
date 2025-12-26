<?php

namespace Karross\Metadata;

class AssociationMetadata extends PropertyMetadata
{
    /**
     * @param class-string $fqcn
     */
    public function __construct(
        public string $name,
        public array $identifier,
        public string $fqcn,
        public PropertyType $type,
        public string $formatter,
    ) {
        parent::__construct($name, false, true, $type, $formatter);
    }
}
