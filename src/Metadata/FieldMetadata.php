<?php

namespace Karross\Metadata;

readonly class FieldMetadata extends PropertyMetadata
{
    /**
     * @param class-string $fqcn
     * @param class-string $formatter
     */
    public function __construct(
        public string $name,
        public string $fqcn,
        public string $formatter,
    ) {
        parent::__construct($name, true, false, $this->formatter);
    }
}
