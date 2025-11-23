<?php

namespace Karross\Formatters;

class Context
{
    public function __construct(
        public readonly ?string $locale = null,
        public readonly ?string $timezone = null,
        public readonly ?int $precision = null,
        public readonly ?string $dateFormat = null,
        public readonly ?string $timeFormat = null,
    ) {}
}
