<?php

namespace Karross\Metadata;

readonly class AssociationMetadata extends PropertyMetadata
{
    /**
     * @param class-string $fqcn
     */
    public function __construct(
        public string $name,
        public array $identifier,
        public string $fqcn,
        public string $formatter,
    ) {
        parent::__construct($name, false, true, $this->formatter);
    }
}
