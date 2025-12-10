<?php

namespace Karross\Formatters;

/**
 * Value object that encapsulates type information from various sources
 * (PHP native type, PHPDoc, Doctrine metadata) for a property.
 */
readonly class PropertyTypeInfo
{
    public function __construct(
        public ?string $phpType = null,
        public ?string $phpDocType = null,
        public ?string $doctrineType = null,
        public bool $isNullable = false,
        public bool $isCollection = false,
        public ?string $collectionItemType = null,
    ) {}

    public function hasPhpType(): bool
    {
        return $this->phpType !== null;
    }

    public function hasPhpDocType(): bool
    {
        return $this->phpDocType !== null;
    }

    public function hasDoctrineType(): bool
    {
        return $this->doctrineType !== null;
    }
}
