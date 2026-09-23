<?php

namespace Karross\Formatters;

final readonly class FormattingContext
{
    public const string DEFAULT_LOCALE = 'en_US';

    private function __construct(
        public ?string $locale,
        public ?string $timezone,
        public ?int $precision,
        public ?string $dateFormat,
        public ?string $timeFormat,
        public ?string $dateTimeFormat,
        public ?string $dateFormatPreset = null,
        public ?string $timeFormatPreset = null,
        public ?string $currency = null,
        public ?string $entitySlug = null,
        public ?string $propertyName = null,
        public bool $ucfirst = false,
        public ?int $maximumFractionDigits = null,
        public ?int $minimumFractionDigits = null,
    ) {
    }

    public static function default(): self
    {
        return new self(null, null, null, null, null, null);
    }

    public static function forLocale(string $locale, ?string $currency = null): self
    {
        return new self($locale, null, null, null, null, null, currency: $currency);
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
        ?int $maximumFractionDigits = null,
        ?int $minimumFractionDigits = null,
    ): self {
        return new self($locale, null, $precision, null, null, null, maximumFractionDigits: $maximumFractionDigits, minimumFractionDigits: $minimumFractionDigits);
    }

    public function with(
        ?string $locale = null,
        ?string $timezone = null,
        ?int $precision = null,
        ?string $dateFormat = null,
        ?string $timeFormat = null,
        ?string $dateTimeFormat = null,
        ?string $dateFormatPreset = null,
        ?string $timeFormatPreset = null,
        ?string $currency = null,
        ?string $entitySlug = null,
        ?string $propertyName = null,
        ?bool $ucfirst = null,
        ?int $maximumFractionDigits = null,
    ): self {
        return new self(
            $locale ?? $this->locale,
            $timezone ?? $this->timezone,
            $precision ?? $this->precision,
            $dateFormat ?? $this->dateFormat,
            $timeFormat ?? $this->timeFormat,
            $dateTimeFormat ?? $this->dateTimeFormat,
            $dateFormatPreset ?? $this->dateFormatPreset,
            $timeFormatPreset ?? $this->timeFormatPreset,
            $currency ?? $this->currency,
            $entitySlug ?? $this->entitySlug,
            $propertyName ?? $this->propertyName,
            $ucfirst ?? $this->ucfirst,
            $maximumFractionDigits ?? $this->maximumFractionDigits,
        );
    }
}
