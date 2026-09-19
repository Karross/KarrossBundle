<?php

namespace Karross\Formatters\Resolvers;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\FieldMapping;
use Karross\Formatters\IntlNumberFormatter;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * Second link of the responsibility chain: the integer family. Accepts a
 * property declared as an integer in PHP ('int'), or with no decisive PHP
 * type ('string' — out-of-range integers come back from the database driver
 * as digit strings — or no type at all) on a smallint, integer or bigint column. A column carrying an
 * enumType describes an enum choice, not an integer. Resolves to
 * IntlNumberFormatter, without any failure risk.
 */
#[AutoconfigureTag('karross.formatter.resolver')]
final class IntegerFormatterResolver implements FormatterResolverInterface
{
    public function accept(?string $phpType, ?FieldMapping $fieldMapping = null): bool
    {
        if (null !== $fieldMapping?->enumType) {
            return false;
        }

        if ('int' === $phpType) {
            return true;
        }

        if (null !== $phpType && 'string' !== $phpType) {
            return false;
        }

        return \in_array($fieldMapping?->type, [Types::SMALLINT, Types::INTEGER, Types::BIGINT], true);
    }

    public function resolve(?string $phpType, ?FieldMapping $fieldMapping = null): string
    {
        return IntlNumberFormatter::class;
    }
}
