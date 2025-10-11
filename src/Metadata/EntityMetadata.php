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
        public readonly array $associations,
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

    public function getAssociations(): array
    {
        return $this->associations;
    }

    public function getIdentifier(): array
    {
        return $this->classMetadata->getIdentifier();
    }

    public function getValue(object $entity, string $field): mixed
    {
        return $this->classMetadata->getFieldValue($entity, $field);
    }

    public function getType(string $fieldName): string
    {
        return $this->classMetadata->getTypeOfField($fieldName);
    }

    public function isEmbedded(string $fieldName): bool
    {
        return str_contains($fieldName, '.') &&
        in_array(strtok($fieldName, '.'), array_keys($this->classMetadata->embeddedClasses));
    }

    public function hasEmbeddedField(): bool
    {
        return $this->getMaxEmbeddedDepth() > 0;
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

    public function getExplodedFields(): array
    {
        $explodedFields = [];
        foreach ($this->getFields() as $field) {
             $explodedFields[$field] = explode('.', $field);
        }

        return $explodedFields;
    }

    public function getFieldLabelsHierarchy(): array
    {
        $tree = [];
        for ($depth = 0; $depth <= $this->getMaxEmbeddedDepth(); $depth++) {

            $tree[$depth] = [];
            foreach ($this->getExplodedFields() as $path => $explodedField) {
                $fieldLabel = $explodedField[$depth] ?? null;
                if ($fieldLabel && !array_key_exists($fieldLabel, $tree[$depth])) {
                    $partialPath = implode('.', array_slice($explodedField, 0, $depth + 1));
                    $tree[$depth][$fieldLabel] = new FieldLabel(
                        label: $fieldLabel,
                        path: $partialPath,
                        depth: $depth,
                        numberOfLeaves: $this->getNumberOfLeaves($partialPath),
                        isLeaf: $depth + 1 === count($explodedField)
                    );
                }
            }
        }

        return $tree;
    }

    private function getNumberOfLeaves(string $partialPath): int
    {
        $count = 0;
        foreach ($this->getFields() as $fieldPath) {
            if (str_starts_with($fieldPath, $partialPath . '.')
                || $fieldPath === $partialPath) {
                $count++;
            }
        }

        return $count;
    }
}
