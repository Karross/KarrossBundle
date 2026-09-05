<?php

namespace Karross\Formatters;

final readonly class FormattingContext
{
    public const DEFAULT_LOCALE = 'en_US';

    private function __construct(
        public ?string $locale,
        public ?string $timezone,
        public ?int $precision,
        public ?string $dateFormat,
        public ?string $timeFormat,
        public ?string $dateTimeFormat,
        public ?string $currency = null,
    ) {
    }

    public static function default(): self
    {
        return new self(null, null, null, null, null, null);
    }

    public static function forLocale(string $locale, ?string $currency = null): self
    {
        return new self($locale, null, null, null, null, null, $currency);
    }

    public static function forDate(
        ?string $locale = null,
        ?string $timezone = null,
        ?string $format = null,
    ): self {
        return new self($locale, $timezone, null, $format, null, null);
    }

    public static function forNumber(
        ?string $locale = null,
        ?int $precision = null,
    ): self {
        return new self($locale, null, $precision, null, null, null);
    }

    public function with(
        ?string $locale = null,
        ?string $timezone = null,
        ?int $precision = null,
        ?string $dateFormat = null,
        ?string $timeFormat = null,
        ?string $dateTimeFormat = null,
        ?string $currency = null,
    ): self {
        return new self(
            $locale ?? $this->locale,
            $timezone ?? $this->timezone,
            $precision ?? $this->precision,
            $dateFormat ?? $this->dateFormat,
            $timeFormat ?? $this->timeFormat,
            $dateTimeFormat ?? $this->dateTimeFormat,
            $currency ?? $this->currency,
        );
    }
}
