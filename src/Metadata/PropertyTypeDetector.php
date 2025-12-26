<?php

namespace Karross\Metadata;

use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use ReflectionNamedType;
use ReflectionProperty;
use ReflectionUnionType;
use UnitEnum;

/**
 * Detects the semantic type of a property by analyzing all available information:
 * - PHP native type hints
 * - PHPDoc annotations
 * - Doctrine type mappings
 * - Association metadata
 */
final class PropertyTypeDetector
{
    /**
     * Detect the semantic type of a property.
     */
    public function detect(
        ?ReflectionProperty $property = null,
        ?string $doctrineType = null,
        bool $isAssociation = false,
    ): PropertyType {
        // Associations have priority
        if ($isAssociation) {
            // For associations, property should not be null
            if ($property === null) {
                return PropertyType::Unknown;
            }
            return $this->detectAssociationType($property);
        }

        // If no ReflectionProperty available, fallback to Doctrine type only
        // This can happen for embedded fields where resolution fails
        if ($property === null) {
            if ($doctrineType !== null) {
                $detected = $this->detectFromDoctrineType($doctrineType);
                if ($detected !== PropertyType::Unknown) {
                    return $detected;
                }
            }
            return PropertyType::Unknown;
        }

        // Try to detect from PHP type (priority)
        $reflectionType = $property->getType();

        if ($reflectionType instanceof ReflectionNamedType) {
            $phpType = $reflectionType->getName();
            $detected = $this->detectFromPhpType($phpType, $doctrineType);

            if ($detected !== PropertyType::Unknown) {
                return $detected;
            }
        } elseif ($reflectionType instanceof ReflectionUnionType) {
            // For union types, try the first non-null type
            foreach ($reflectionType->getTypes() as $type) {
                if ($type instanceof ReflectionNamedType && $type->getName() !== 'null') {
                    $detected = $this->detectFromPhpType($type->getName(), $doctrineType);

                    if ($detected !== PropertyType::Unknown) {
                        return $detected;
                    }
                }
            }
        }

        // Fallback to Doctrine type
        if ($doctrineType !== null) {
            $detected = $this->detectFromDoctrineType($doctrineType);

            if ($detected !== PropertyType::Unknown) {
                return $detected;
            }
        }

        // Try PHPDoc as last resort
        $phpDocType = $this->extractPhpDocType($property);
        if ($phpDocType !== null) {
            if (str_contains($phpDocType, 'array<') || str_ends_with($phpDocType, '[]')) {
                return PropertyType::Array;
            }
        }

        return PropertyType::Unknown;
    }

    private function detectFromPhpType(string $phpType, ?string $doctrineType): PropertyType
    {
        return match ($phpType) {
            'bool' => PropertyType::Boolean,
            'int' => PropertyType::Integer,
            'float' => PropertyType::Float,
            'string' => PropertyType::String,
            'array' => PropertyType::Array,
            'DateTime', 'DateTimeImmutable', DateTimeInterface::class => $this->refineDateTimeType($doctrineType),
            default => $this->detectFromClassName($phpType),
        };
    }

    private function detectFromClassName(string $className): PropertyType
    {
        if (!class_exists($className) && !interface_exists($className)) {
            return PropertyType::Unknown;
        }

        // Enum
        if (is_subclass_of($className, UnitEnum::class)) {
            return PropertyType::Enum;
        }

        // Stringable objects
        if (method_exists($className, '__toString')) {
            return PropertyType::String;
        }

        return PropertyType::Unknown;
    }

    private function detectFromDoctrineType(string $doctrineType): PropertyType
    {
        return match ($doctrineType) {
            Types::BOOLEAN => PropertyType::Boolean,

            Types::SMALLINT,
            Types::INTEGER,
            Types::BIGINT => PropertyType::Integer,

            Types::DECIMAL,
            Types::FLOAT => PropertyType::Float,

            Types::STRING,
            Types::ASCII_STRING,
            Types::GUID => PropertyType::String,

            Types::TEXT => PropertyType::Text,

            Types::DATE_MUTABLE,
            Types::DATE_IMMUTABLE => PropertyType::Date,

            Types::TIME_MUTABLE,
            Types::TIME_IMMUTABLE => PropertyType::Time,

            Types::DATETIME_MUTABLE,
            Types::DATETIME_IMMUTABLE,
            Types::DATETIMETZ_MUTABLE,
            Types::DATETIMETZ_IMMUTABLE => PropertyType::DateTime,

            default => PropertyType::Unknown,
        };
    }

    /**
     * Refine DateTime type based on Doctrine type if available.
     */
    private function refineDateTimeType(?string $doctrineType): PropertyType
    {
        if ($doctrineType === null) {
            return PropertyType::DateTime;
        }

        return match ($doctrineType) {
            Types::DATE_MUTABLE,
            Types::DATE_IMMUTABLE => PropertyType::Date,

            Types::TIME_MUTABLE,
            Types::TIME_IMMUTABLE => PropertyType::Time,

            Types::DATETIME_MUTABLE,
            Types::DATETIME_IMMUTABLE,
            Types::DATETIMETZ_MUTABLE,
            Types::DATETIMETZ_IMMUTABLE => PropertyType::DateTime,

            default => PropertyType::DateTime,
        };
    }

    private function detectAssociationType(ReflectionProperty $property): PropertyType
    {
        $reflectionType = $property->getType();

        // Collections are typically arrays or Collection types
        if ($reflectionType instanceof ReflectionNamedType) {
            $typeName = $reflectionType->getName();

            // Common collection types
            if ($typeName === 'array' || str_contains($typeName, 'Collection')) {
                return PropertyType::Collection;
            }
        }

        // Check PHPDoc for collection hints
        $phpDocType = $this->extractPhpDocType($property);
        if ($phpDocType !== null && (str_contains($phpDocType, 'Collection') || str_contains($phpDocType, '[]'))) {
            return PropertyType::Collection;
        }

        // Default to single-valued association
        return PropertyType::Single;
    }

    private function extractPhpDocType(ReflectionProperty $property): ?string
    {
        $docComment = $property->getDocComment();
        if ($docComment === false) {
            return null;
        }

        // Extract @var annotation
        if (preg_match('/@var\s+([^\s]+)/', $docComment, $matches)) {
            return $matches[1];
        }

        return null;
    }
}

