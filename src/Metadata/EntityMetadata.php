<?php

namespace Karross\Metadata;

use Doctrine\Persistence\Mapping\ClassMetadata;
use Karross\Actions\Action;

readonly class EntityMetadata
{
    /**
     * @param Action[] $actions
     * @param PropertyInterface[] $properties
     */
    public function __construct(
        public string         $slug,
        public array          $actions,
        public array          $properties,
        private ClassMetadata $classMetadata,
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

    public function getProperties(): array
    {
        return $this->properties;
    }

    public function getIdentifier(): array
    {
        return $this->classMetadata->getIdentifier();
    }

    public function getValue(object $entity, string $property): mixed
    {
        return $this->classMetadata->getFieldValue($entity, $property);
    }

    public function getTypeOfField(string $fieldName): string
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
                fn(PropertyInterface $property) => substr_count($property->name, '.'),
                $this->getProperties()
            )
        );
    }

    public function getExplodedProperties(): array
    {
        $explodedProperties = [];
        foreach ($this->getProperties() as $property) {
             $explodedProperties[$property->name] = explode('.', $property->name);
        }

        return $explodedProperties;
    }

    /**
     * This method is used for example in index/items_embedded.html.twig template to display the table head.
     */
    public function getPropertyLabelsHierarchy(): array
    {
        $tree = [];
        for ($depth = 0; $depth <= $this->getMaxEmbeddedDepth(); $depth++) {

            $tree[$depth] = [];
            foreach ($this->getExplodedProperties() as $explodedProperty) {
                $fieldLabel = $explodedProperty[$depth] ?? null;
                if ($fieldLabel && !array_key_exists($fieldLabel, $tree[$depth])) {
                    $partialPath = implode('.', array_slice($explodedProperty, 0, $depth + 1));
                    $tree[$depth][$fieldLabel] = new FieldLabel(
                        label: $fieldLabel,
                        path: $partialPath,
                        depth: $depth,
                        numberOfLeaves: $this->getNumberOfLeaves($partialPath),
                        isLeaf: $depth + 1 === count($explodedProperty)
                    );
                }
            }
        }

        return $tree;
    }

    private function getNumberOfLeaves(string $partialPath): int
    {
        $count = 0;
        foreach ($this->getProperties() as $property) {
            if (str_starts_with($property->name, $partialPath . '.')
                || $property->name === $partialPath) {
                $count++;
            }
        }

        return $count;
    }
}
