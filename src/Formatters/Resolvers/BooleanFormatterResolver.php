<?php

namespace Karross\Formatters\Resolvers;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\FieldMapping;
use Karross\Formatters\Boolean\TrueFalseFormatter;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * First link of the responsibility chain: the boolean family. Accepts a
 * property as soon as it is declared a boolean, whether by its PHP type
 * ('bool') or by its Doctrine type (boolean). A column carrying an enumType
 * describes an enum choice, not a boolean. Resolves to
 * TrueFalseFormatter, without any failure risk.
 */
#[AutoconfigureTag('karross.formatter.resolver')]
final class BooleanFormatterResolver implements FormatterResolverInterface
{
    public function accept(?string $phpType, ?FieldMapping $fieldMapping = null): bool
    {
        if (null !== $fieldMapping?->enumType) {
            return false;
        }

        return 'bool' === $phpType || Types::BOOLEAN === $fieldMapping?->type;
    }

    public function resolve(?string $phpType, ?FieldMapping $fieldMapping = null): string
    {
        return TrueFalseFormatter::class;
    }
}
