<?php

namespace Karross\Formatters\Resolvers;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\FieldMapping;
use Karross\Formatters\StringFormatter;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * Fourth link of the responsibility chain: the string family. Accepts a
 * property declared as a string in PHP ('string') on a string-like
 * Doctrine column (string, text, ascii_string, guid), or with no decisive
 * PHP type on the same columns. A column carrying an enumType describes an
 * enum choice, not a string. Resolves to StringFormatter, without any
 * failure risk.
 *
 * Cases handled elsewhere:
 * - phpType 'string' on integer/decimal columns → IntegerFormatterResolver /
 *   FloatFormatterResolver (numeric families win when Doctrine says numeric).
 * - phpType 'string' on boolean column → BooleanFormatterResolver.
 * - Classes with __toString() → resolvePhpClass() in FormatterResolver fallback.
 * - json/blob → future tickets (Array/structured).
 */
#[AutoconfigureTag('karross.formatter.resolver')]
final class StringFormatterResolver implements FormatterResolverInterface
{
    private const array STRING_DOCTRINE_TYPES = [
        Types::STRING,
        Types::ASCII_STRING,
        Types::GUID,
        Types::TEXT,
    ];

    public function accept(?string $phpType, ?FieldMapping $fieldMapping = null): bool
    {
        if (null !== $fieldMapping?->enumType) {
            return false;
        }

        if ('string' === $phpType) {
            return \in_array($fieldMapping?->type, self::STRING_DOCTRINE_TYPES, true)
                || null === $fieldMapping?->type;
        }

        if (null === $phpType) {
            return \in_array($fieldMapping?->type, self::STRING_DOCTRINE_TYPES, true);
        }

        return false;
    }

    public function resolve(?string $phpType, ?FieldMapping $fieldMapping = null): string
    {
        return StringFormatter::class;
    }
}
