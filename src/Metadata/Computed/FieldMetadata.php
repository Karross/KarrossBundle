<?php

namespace Karross\Metadata\Computed;

/**
 * Field read-model (discriminator over PropertyMetadata) carrying the column
 * facts projected from the Doctrine field mapping.
 */
readonly class FieldMetadata extends PropertyMetadata
{
    /**
     * @param class-string               $fqcn
     * @param string[]                   $identifier       empty for fields
     * @param class-string               $formatter
     * @param array<string, string|bool> $formatterOptions
     * @param list<string>               $typeHierarchy
     */
    public function __construct(
        string $name,
        string $fqcn,
        ?string $phpType,
        ?string $doctrineType,
        bool $conflict,
        array $identifier,
        string $formatter,
        array $formatterOptions = [],
        array $typeHierarchy = [],
        ?string $widget = null,
        ?string $entitySlug = null,
        public ?int $length = null,
        public ?int $precision = null,
        public ?int $scale = null,
        public ?string $enumType = null,
        public bool $unsigned = false,
        public bool $fixed = false,
        public bool $nullable = false,
        public bool $id = false,
        public bool $version = false,
        public bool $generated = false,
    ) {
        parent::__construct(
            $name,
            $fqcn,
            $phpType,
            $doctrineType,
            $conflict,
            $identifier,
            $formatter,
            $formatterOptions,
            $typeHierarchy,
            $widget,
            $entitySlug,
        );
    }
}
