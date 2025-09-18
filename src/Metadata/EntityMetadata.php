<?php

namespace Karross\Metadata;

use Doctrine\Persistence\Mapping\ClassMetadata;
use Karross\Actions\Action;

class EntityMetadata
{
    public function __construct(
        public readonly string $slug,
        /**
         * @var Action[]
         */
        public readonly array $actions,
        public readonly ClassMetadata $classMetadata,
    ) {}

    public function getFqcn(): string
    {
        return $this->classMetadata->getName();
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function getActions(): array
    {
        return $this->actions;
    }

    public function getFields(): array
    {
        return $this->classMetadata->getFieldNames();
    }

    public function isEmbedded(string $fieldName): bool
    {
        return str_contains($fieldName, '.') &&
        in_array(strtok($fieldName, '.'), array_keys($this->classMetadata->embeddedClasses));
    }

    public function getMaxEmbeddedDepth(): int
    {
        return max(
            array_map(
                fn(string $fieldName) => substr_count($fieldName, '.'),
                $this->getFields()
            )
        );
    }

    public function getType(string $fieldName): string
    {
        return $this->classMetadata->getTypeOfField($fieldName);
    }
}
