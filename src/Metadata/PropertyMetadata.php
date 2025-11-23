<?php

namespace Karross\Metadata;

abstract readonly class PropertyMetadata
{
    /**
     * @param class-string $formatter
     */
    public function __construct(
        string $name,
        public bool $isField,
        public bool $isAssociation,
        string $formatter
    ) {

    }

    public function format(mixed $value) {
        return $this->formatter::format($value);
    }
}
