<?php

namespace Karross\Formatters;

use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use Karross\Formatters\BooleanFormatter;
use Karross\Formatters\NoFormatter;
use Karross\Formatters\NotAvailableFormatter;
use ReflectionNamedType;
use ReflectionProperty;
use ReflectionUnionType;
use UnitEnum;

/**
 * Resolves the appropriate formatter for a property based on all available type information.
 */
class FormatterResolver
{
    /**
     * @return class-string<ValueFormatterInterface>
     */
    public function resolve(
        ReflectionProperty $property,
        ?string $doctrineType = null
    ): string {
        $typeInfo = $this->buildTypeInfo($property, $doctrineType);

        // Try to resolve from PHP native type first
        if ($typeInfo->hasPhpType()) {
            $formatter = $this->resolveFromPhpType($typeInfo);
            if ($formatter !== null) {
                return $formatter;
            }
        }

        // Try PHPDoc type hints
        if ($typeInfo->hasPhpDocType()) {
            $formatter = $this->resolveFromPhpDoc($typeInfo);
            if ($formatter !== null) {
                return $formatter;
            }
        }

        // Fall back to Doctrine type
        if ($typeInfo->hasDoctrineType()) {
            $formatter = $this->resolveFromDoctrineType($typeInfo);
            if ($formatter !== null) {
                return $formatter;
            }
        }

        return NotAvailableFormatter::class;
    }

    private function buildTypeInfo(ReflectionProperty $property, ?string $doctrineType): PropertyTypeInfo
    {
        $reflectionType = $property->getType();
        $phpType = null;
        $isNullable = false;

        if ($reflectionType instanceof ReflectionNamedType) {
            $phpType = $reflectionType->getName();
            $isNullable = $reflectionType->allowsNull();
        } elseif ($reflectionType instanceof ReflectionUnionType) {
            // Handle union types - for now, take the first non-null type
            foreach ($reflectionType->getTypes() as $type) {
                if ($type instanceof ReflectionNamedType && $type->getName() !== 'null') {
                    $phpType = $type->getName();
                    break;
                }
            }
            $isNullable = $reflectionType->allowsNull();
        }

        // Parse PHPDoc for additional type information
        $phpDocType = $this->extractPhpDocType($property);
        
        // Detect collections
        $isCollection = false;
        $collectionItemType = null;
        if ($phpType === 'array' || str_contains($phpDocType ?? '', 'array<')) {
            $isCollection = true;
            $collectionItemType = $this->extractCollectionItemType($phpDocType);
        }

        return new PropertyTypeInfo(
            phpType: $phpType,
            phpDocType: $phpDocType,
            doctrineType: $doctrineType,
            isNullable: $isNullable,
            isCollection: $isCollection,
            collectionItemType: $collectionItemType,
        );
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

    private function extractCollectionItemType(?string $phpDocType): ?string
    {
        if ($phpDocType === null) {
            return null;
        }

        // Match array<Type> or Type[]
        if (preg_match('/array<([^>]+)>/', $phpDocType, $matches)) {
            return $matches[1];
        }

        if (preg_match('/([^\[\]]+)\[\]/', $phpDocType, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * @return class-string<ValueFormatterInterface>|null
     */
    private function resolveFromPhpType(PropertyTypeInfo $typeInfo): ?string
    {
        return match ($typeInfo->phpType) {
            'bool' => BooleanFormatter::class,
            'int', 'float' => IntlNumberFormatter::class,
            'string' => StringFormatter::class,
            DateTimeInterface::class, 'DateTime', DateTimeImmutable::class => DateTimeFormatter::class,
            'array' => $this->resolveArrayFormatter($typeInfo),
            default => $this->resolveClassType($typeInfo->phpType),
        };
    }

    /**
     * @return class-string<ValueFormatterInterface>|null
     */
    private function resolveFromPhpDoc(PropertyTypeInfo $typeInfo): ?string
    {
        // Handle collection types from PHPDoc
        if ($typeInfo->isCollection && $typeInfo->collectionItemType) {
            // For now, we don't have array formatters, return N/A
            // TODO: Implement collection formatters
            return NotAvailableFormatter::class;
        }

        return null;
    }

    /**
     * @return class-string<ValueFormatterInterface>|null
     */
    private function resolveFromDoctrineType(PropertyTypeInfo $typeInfo): ?string
    {
        return match ($typeInfo->doctrineType) {
            Types::SMALLINT,
            Types::INTEGER,
            Types::BIGINT,
            Types::DECIMAL,
            Types::FLOAT => IntlNumberFormatter::class,

            Types::STRING,
            Types::ASCII_STRING,
            Types::TEXT,
            Types::GUID => StringFormatter::class,

            Types::BOOLEAN => BooleanFormatter::class,

            Types::DATE_MUTABLE,
            Types::DATE_IMMUTABLE => DateFormatter::class,

            Types::DATETIME_MUTABLE,
            Types::DATETIME_IMMUTABLE,
            Types::DATETIMETZ_MUTABLE,
            Types::DATETIMETZ_IMMUTABLE => DateTimeFormatter::class,

            Types::TIME_MUTABLE,
            Types::TIME_IMMUTABLE => TimeFormatter::class,

            default => null,
        };
    }

    /**
     * @return class-string<ValueFormatterInterface>|null
     */
    private function resolveArrayFormatter(PropertyTypeInfo $typeInfo): ?string
    {
        // If we have collection item type information, we could format accordingly
        // For now, return N/A for arrays
        return NotAvailableFormatter::class;
    }

    /**
     * @return class-string<ValueFormatterInterface>|null
     */
    private function resolveClassType(?string $className): ?string
    {
        if ($className === null) {
            return null;
        }

        // Check if it's an enum
        if (is_subclass_of($className, UnitEnum::class)) {
            return EnumFormatter::class;
        }

        // Check if it implements __toString or Stringable
        if (method_exists($className, '__toString')) {
            return StringFormatter::class;
        }

        return null;
    }
}
