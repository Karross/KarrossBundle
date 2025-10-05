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

    public function getFieldsAsTree(): array
    {
        $tree = [];

        foreach ($this->getFields() as $fieldName) {
            $parts = explode('.', $fieldName);
            $this->insertFieldParts($tree, $parts);
        }

        return $tree;
    }

    private function insertFieldParts(array &$tree, array $parts): void
    {
        $key = array_shift($parts);

        if (!isset($tree[$key])) {
            $tree[$key] = ['children' => [], 'isLeaf' => false];
        }

        if (empty($parts)) {
            $tree[$key]['isLeaf'] = true;
        } else {
            $this->insertFieldParts($tree[$key]['children'], $parts);
        }
    }

    public function getLeafFields(): array
    {
        $tree = $this->getFieldsAsTree();
        $leaves = [];
        $this->collectLeaves($tree, [], $leaves);
        return $leaves;
    }

    private function collectLeaves(array $tree, array $path, array &$leaves): void
    {
        foreach ($tree as $name => $node) {
            $fullPath = array_merge($path, [$name]);
            if ($node['isLeaf']) {
                $leaves[] = implode('.', $fullPath);
            } else {
                $this->collectLeaves($node['children'], $fullPath, $leaves);
            }
        }
    }

    public function getHeaderMatrix(): array
    {
        $tree = $this->getFieldsAsTree();
        $maxDepth = $this->getMaxEmbeddedDepth();
        $matrix = [];

        $this->fillHeaderMatrix($tree, 0, $matrix, $maxDepth);

        return $matrix;
    }

    private function fillHeaderMatrix(array $tree, int $depth, array &$matrix, int $maxDepth): int
    {
        $matrix[$depth] ??= [];
        $totalCols = 0;

        foreach ($tree as $name => $node) {
            $colspan = $this->countLeafNodes($node);
            $rowspan = $node['isLeaf'] ? ($maxDepth - $depth + 1) : 1;

            $matrix[$depth][] = [
                'label' => $name,
                'colspan' => $colspan,
                'rowspan' => $rowspan,
            ];

            if (!empty($node['children'])) {
                $this->fillHeaderMatrix($node['children'], $depth + 1, $matrix, $maxDepth);
            }

            $totalCols += $colspan;
        }

        return $totalCols;
    }

    private function countLeafNodes(array $node): int
    {
        if (empty($node['children'])) {
            return 1;
        }

        $count = 0;
        foreach ($node['children'] as $child) {
            $count += $this->countLeafNodes($child);
        }
        return $count;
    }

}
