<?php

namespace Karross\Formatters\Options;

use Karross\Formatters\DateTime\DateFormatter;
use Karross\Formatters\DateTime\DateTimeFormatter;
use Karross\Formatters\DateTime\TimeFormatter;

/**
 * Resolves the `datetime_format` option of Date/Time/DateTime formatters at build time.
 * Other formatters pass through untouched.
 */
final readonly class DateTimeOptionsResolver
{
    public function __construct(private NamedDatetimeFormats $formats)
    {
    }

    /**
     * Turns the format name into its resolved patterns/presets table.
     *
     * Example (DateFormatter, no option):
     *   returns ["datetime_format" => ["presets" => ["date" => "medium"]]]
     *
     * Example (DateTimeFormatter with config datetime_format: "my_custom_datetime_format"):
     *   returns ["datetime_format" => ["patterns" => ["fr" => "d MMMM yyyy 'à' HH:mm", ...]]]
     *
     * Example (StringFormatter):
     *   returns the options as-is
     *
     * @param array<string, mixed> $formatterOptions
     *
     * @return array<string, mixed>
     *
     * @throws \InvalidArgumentException when the format name is unknown
     */
    public function resolve(string $formatter, array $formatterOptions, string $fqcn, string $property): array
    {
        if (!\in_array($formatter, [DateFormatter::class, TimeFormatter::class, DateTimeFormatter::class], true)) {
            return $formatterOptions;
        }

        $explicit = $formatterOptions['datetime_format'] ?? null;
        $name = \is_string($explicit) ? $explicit : NamedDatetimeFormats::IMPLICIT_BY_FORMATTER[$formatter];

        $resolved = $this->formats->configFor($name);
        if (null === $resolved) {
            throw new \InvalidArgumentException(\sprintf('Unknown datetime format "%s" for property "%s" of "%s".', $name, $property, $fqcn));
        }

        $formatterOptions['datetime_format'] = $resolved;

        return $formatterOptions;
    }
}
