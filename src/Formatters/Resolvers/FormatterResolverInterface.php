<?php

namespace Karross\Formatters\Resolvers;

use Doctrine\ORM\Mapping\FieldMapping;
use Karross\Formatters\ValueFormatterInterface;

/**
 * A link of the formatter resolution chain: answers whether a property belongs
 * to its type family (accept) and, when it does, hands back the formatter class
 * it stands for (resolve).
 */
interface FormatterResolverInterface
{
    public function accept(?string $phpType, ?FieldMapping $fieldMapping = null): bool;

    /**
     * @return class-string<ValueFormatterInterface>
     */
    public function resolve(?string $phpType, ?FieldMapping $fieldMapping = null): string;
}
