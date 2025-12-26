<?php

namespace Karross\Metadata;

class FieldMetadata extends PropertyMetadata
{
    /**
     * @param class-string $fqcn
     * @param class-string $formatter
     */
    public function __construct(
        public string $name,
        public string $fqcn,
        public PropertyType $type,
        public string $formatter,
    ) {
        parent::__construct($name, true, false, $type, $formatter);
    }
}
