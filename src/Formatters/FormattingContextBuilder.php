<?php

namespace Karross\Formatters;

use Karross\Formatters\DateTime\DateFormatter;
use Karross\Formatters\DateTime\DateTimeFormatter;
use Karross\Formatters\DateTime\TimeFormatter;
use Karross\Metadata\Computed\PropertyMetadata;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Builds the FormattingContext the formatters run with, from property metadata + request locale.
 * Static options (currency, ucfirst, fraction digits) are copied from the build-resolved metadata;
 * the locale and the datetime pattern choice come from the current request.
 */
final readonly class FormattingContextBuilder
{
    public function __construct(private RequestStack $requestStack)
    {
    }

    /**
     * Example (price, formatter_options.currency: EUR, locale fr):
     *   context carries locale "fr", currency "EUR", maximumFractionDigits from options
     *
     * Example (createdAt with datetime_format resolved at build):
     *   the fr pattern is injected into dateTimeFormat
     */
    public function build(PropertyMetadata $property): FormattingContext
    {
        $currency = $property->formatterOptions['currency'] ?? null;
        $locale = $this->requestStack->getCurrentRequest()?->getLocale() ?? FormattingContext::DEFAULT_LOCALE;

        $context = FormattingContext::forLocale(
            $locale,
            \is_string($currency) ? $currency : null,
        )->with(
            entitySlug: $property->entitySlug,
            propertyName: $property->name,
            ucfirst: (bool) ($property->formatterOptions['ucfirst'] ?? false),
            maximumFractionDigits: \is_int($property->formatterOptions['maximum_fraction_digits'] ?? null) ? $property->formatterOptions['maximum_fraction_digits'] : null,
        );

        return $this->withNamedDatetimeFormat($context, $property, $locale);
    }

    /**
     * Injects the build-resolved datetime_format into the formatter slot.
     * Pattern first, then ICU preset. No registry at render time.
     *
     * Example (DateTimeFormatter, locale fr, patterns has "fr"):
     *   dateTimeFormat becomes "d MMMM yyyy 'à' HH:mm"
     *
     * Example (locale de, no "de" key but has "default"):
     *   uses the "default" pattern
     *
     * Example (only presets, no matching pattern):
     *   dateFormatPreset "medium" — ICU picks the motif for the locale
     *
     * Example (no datetime_format key, e.g. a string property):
     *   context unchanged
     */
    private function withNamedDatetimeFormat(FormattingContext $context, PropertyMetadata $property, string $locale): FormattingContext
    {
        $resolved = $property->formatterOptions['datetime_format'] ?? null;
        if (!\is_array($resolved)) {
            return $context;
        }

        $pattern = $this->pickPattern($resolved['patterns'] ?? null, $locale);
        $presets = \is_array($resolved['presets'] ?? null) ? $resolved['presets'] : [];
        $datePreset = \is_string($presets['date'] ?? null) ? $presets['date'] : null;
        $timePreset = \is_string($presets['time'] ?? null) ? $presets['time'] : null;

        return match ($property->formatter) {
            DateFormatter::class => $context->with(
                dateFormat: $pattern,
                dateFormatPreset: $datePreset,
            ),
            TimeFormatter::class => $context->with(
                timeFormat: $pattern,
                timeFormatPreset: $timePreset,
            ),
            DateTimeFormatter::class => $context->with(
                dateTimeFormat: $pattern,
                dateFormatPreset: $datePreset,
                timeFormatPreset: $timePreset,
            ),
            default => $context,
        };
    }

    /**
     * Picks the pattern for the locale: exact → language → default.
     *
     * Example patterns ["fr" => "d MMMM yyyy", "default" => "yyyy-MM-dd"]:
     *   locale "fr" returns "d MMMM yyyy"
     *   locale "fr_CA" returns "d MMMM yyyy" (language fallback)
     *   locale "de" returns "yyyy-MM-dd" (default fallback)
     *
     * @param mixed $patterns locale → ICU pattern map from the metadata
     */
    private function pickPattern(mixed $patterns, string $locale): ?string
    {
        if (!\is_array($patterns) || [] === $patterns) {
            return null;
        }

        foreach ([$locale, ...$this->languageKeys($locale), 'default'] as $key) {
            $candidate = $patterns[$key] ?? null;
            if (\is_string($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * Returns the language part of a locale, for pattern fallback.
     *
     * Example: languageKeys("fr_CA") returns ["fr"]
     * Example: languageKeys("fr-CA") returns ["fr"]
     * Example: languageKeys("fr") returns []
     *
     * @return list<string>
     */
    private function languageKeys(string $locale): array
    {
        $keys = [];
        foreach (['_', '-'] as $separator) {
            $position = strpos($locale, $separator);
            if (false !== $position && 0 !== $position) {
                $keys[] = substr($locale, 0, $position);
            }
        }

        return $keys;
    }
}
