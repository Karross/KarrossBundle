<?php

namespace Karross\Metadata;

use Karross\Formatters\FormattingContext;

class PropertyMetadata
{
    /**
     * @param class-string $formatter
     */
    public function __construct(
        public string $name,
        public bool $isField,
        public bool $isAssociation,
        public PropertyType $type,
        public string $formatter
    ) {

    }

    public function format(mixed $value, ?FormattingContext $context = null): string
    {
        return $this->formatter::format($value, $context);
    }
}
