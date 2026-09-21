<?php

namespace Karross\Formatters\Resolvers;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\FieldMapping;
use Karross\Formatters\IntlNumberFormatter;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * Third link of the responsibility chain: the float family. Accepts a
 * property declared as a float in PHP ('float'), or with no decisive PHP
 * type ('string' or no type at all) on a decimal or float column. A column
 * carrying an enumType describes an enum choice, not a float. Resolves to
 * IntlNumberFormatter, without any failure risk.
 */
#[AutoconfigureTag('karross.formatter.resolver')]
final class FloatFormatterResolver implements FormatterResolverInterface
{
    public function accept(?string $phpType, ?FieldMapping $fieldMapping = null): bool
    {
        if (null !== $fieldMapping?->enumType) {
            return false;
        }

        if ('float' === $phpType) {
            return true;
        }

        if (null !== $phpType && 'string' !== $phpType) {
            return false;
        }

        return \in_array($fieldMapping?->type, [Types::DECIMAL, Types::FLOAT], true);
    }

    public function resolve(?string $phpType, ?FieldMapping $fieldMapping = null): string
    {
        return IntlNumberFormatter::class;
    }
}
