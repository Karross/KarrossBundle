<?php

namespace Karross\Metadata\Collect;

/**
 * Pure Reflection helpers used at build time. No state, no DI.
 *
 * Examples:
 *   phpType(reflect(Article, "title")) returns "string"
 *   phpType(reflect(Article, "publishedAt")) returns "DateTimeImmutable"
 *   phpType(reflect(Article, "viewCount")) returns "int"
 *   phpType(null) returns null
 *
 * Embedded path navigation lives in {@see EmbeddedPropertyNavigator}.
 */
final class PhpTypeInspector
{
    /**
     * Returns the non-null PHP type of a property, or null.
     *
     * Examples:
     *   phpType(Article::$title) returns "string"
     *   phpType(Article::$publishedAt) returns "DateTimeImmutable"
     *   phpType(Article::$status) returns "ArticleStatus" (enum)
     *   phpType(untyped property) returns null
     */
    public static function phpType(?\ReflectionProperty $property): ?string
    {
        if (null === $property) {
            return null;
        }

        $type = $property->getType();

        if ($type instanceof \ReflectionNamedType) {
            return $type->getName();
        }

        if ($type instanceof \ReflectionUnionType) {
            foreach ($type->getTypes() as $member) {
                if ($member instanceof \ReflectionNamedType && 'null' !== $member->getName()) {
                    return $member->getName();
                }
            }
        }

        return null;
    }

    /**
     * Returns the ReflectionProperty for a field name, or null.
     *
     * Examples:
     *   reflectionProperty(Article, "title") returns the title property
     *   reflectionProperty(Article, "identity.firstname")
     *     navigates the embedded and returns firstname
     *   reflectionProperty(Article, "missing") returns null
     *
     * @template T of object
     *
     * @param \ReflectionClass<T> $reflectionClass
     */
    public static function reflectionProperty(\ReflectionClass $reflectionClass, string $fieldName): ?\ReflectionProperty
    {
        if ($reflectionClass->hasProperty($fieldName)) {
            return $reflectionClass->getProperty($fieldName);
        }

        if (!str_contains($fieldName, '.')) {
            return null;
        }

        return EmbeddedPropertyNavigator::from($reflectionClass, explode('.', $fieldName));
    }
}
