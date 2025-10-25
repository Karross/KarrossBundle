<?php

namespace Karross\Metadata;
class Field implements PropertyInterface
{
    /**
     * @param class-string $fqcn
     */
    public function __construct(
        public readonly string $name,
        public readonly string $fqcn,
    ) {}

    public function isField(): bool
    {
        return true;
    }

    public function isAssociation(): bool
    {
        return false;
    }
}
