<?php

namespace Karross\Formatters;

interface ValueFormatterInterface
{
    public function format(mixed $value, ?FormattingContext $context = null): string;
}
