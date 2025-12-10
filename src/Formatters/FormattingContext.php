<?php

namespace Karross\Formatters;

use IntlDateFormatter;

final readonly class FormattingContext
{
    public const DEFAULT_LOCALE = 'en_US';
    private function __construct(
        public ?string $locale,
        public ?string $timezone,
        public ?int $precision,
        public ?string $dateFormat,
        public ?string $timeFormat,
    ) {}

    public static function default(): self
    {
        return new self(null, null, null, null, null);
    }

    public static function forLocale(string $locale): self
    {
        return new self($locale, null, null, null, null);
    }

    public static function forDate(
        ?string $locale = null,
        ?string $timezone = null,
        ?string $format = null,
    ): self {
        return new self($locale, $timezone, null, $format, null);
    }

    public static function forNumber(
        ?string $locale = null,
        ?int $precision = null
    ): self {
        return new self($locale, null, $precision, null, null);
    }

    public function with(
        ?string $locale = null,
        ?string $timezone = null,
        ?int $precision = null,
        ?string $dateFormat = null,
        ?string $timeFormat = null,
    ): self {
        return new self(
            $locale     ?? $this->locale,
            $timezone   ?? $this->timezone,
            $precision  ?? $this->precision,
            $dateFormat ?? $this->dateFormat,
            $timeFormat ?? $this->timeFormat,
        );
    }
}
