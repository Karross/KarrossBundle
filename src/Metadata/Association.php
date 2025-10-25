<?php

namespace Karross\Metadata;
readonly class Association implements PropertyInterface
{
    /**
     * @param class-string $fqcn
     */
    public function __construct(
        public string $name,
        public array  $identifier,
        public string $fqcn,
    ) {}

    public function isField(): bool
    {
        return false;
    }

    public function isAssociation(): bool
    {
        return true;
    }
}
