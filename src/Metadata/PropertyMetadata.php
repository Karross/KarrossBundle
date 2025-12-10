<?php

namespace Karross\Metadata;

class PropertyMetadata
{
    /**
     * @param class-string $formatter
     */
    public function __construct(
        public string $name,
        public bool $isField,
        public bool $isAssociation,
        public string $formatter
    ) {

    }

    public function format(mixed $value) {
        return $this->formatter::format($value);
    }
}
