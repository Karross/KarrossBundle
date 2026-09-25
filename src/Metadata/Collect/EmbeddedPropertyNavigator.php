<?php

namespace Karross\Metadata\Collect;

/**
 * Walks an embedded field path across class types. Pure Reflection, no state.
 *
 * Example: App\Entity\Article has an $identity property of type Identity.
 *   from(Article, ["identity", "firstname"]) returns Identity::$firstname
 *   from(Article, ["identity", "missing"]) returns null
 *   from(Article, ["unknown", "x"]) returns null
 */
final class EmbeddedPropertyNavigator
{
    /**
     * Returns the property at the end of the path, or null.
     *
     * Example for path ["identity", "firstname"]:
     *   reads Article::$identity (type Identity),
     *   then Identity::$firstname, and returns it.
     *
     * Returns null as soon as one segment does not exist.
     *
     * @template T of object
     *
     * @param \ReflectionClass<T> $root
     * @param string[]            $parts
     */
    public static function from(\ReflectionClass $root, array $parts): ?\ReflectionProperty
    {
        $currentClass = $root;
        $currentProperty = null;

        foreach ($parts as $index => $part) {
            if (!$currentClass->hasProperty($part)) {
                return null;
            }

            $currentProperty = $currentClass->getProperty($part);

            if ($index === \count($parts) - 1) {
                return $currentProperty;
            }

            $currentClass = self::nextClass($currentProperty);
            if (null === $currentClass) {
                return null;
            }
        }

        return $currentProperty;
    }

    /**
     * Returns the class of a property type, or null for builtins.
     *
     * Example: Identity::$firstname (type Identity) returns Identity
     *          $title (type string) returns null
     *
     * @return \ReflectionClass<object>|null
     */
    private static function nextClass(\ReflectionProperty $property): ?\ReflectionClass
    {
        $type = $property->getType();
        if (!$type instanceof \ReflectionNamedType || $type->isBuiltin()) {
            return null;
        }

        $className = $type->getName();

        return class_exists($className) ? new \ReflectionClass($className) : null;
    }
}
